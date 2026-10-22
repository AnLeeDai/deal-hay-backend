<?php

use App\Models\User;
use App\Services\ReverbProtocolHealth;
use App\Services\ServerDiagnostics;
use Aws\Command;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('requires a signed in active user to measure servers', function (): void {
    $this->getJson('/api/diagnostics')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->customer()->create(['is_active' => false]));
    $this->getJson('/api/diagnostics')->assertForbidden();
});

it('returns measured database and cache latency while skipping unconfigured remote services', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());
    config(['filesystems.disks.r2.driver' => 'local', 'broadcasting.default' => 'null']);

    $response = $this->getJson('/api/diagnostics')
        ->assertOk()
        ->assertHeaderContains('Cache-Control', 'no-store')
        ->assertJsonPath('checks.database.status', 'ok')
        ->assertJsonPath('checks.cache.status', 'ok')
        ->assertJsonPath('checks.storage.status', 'skipped')
        ->assertJsonPath('checks.storage.latency_ms', null)
        ->assertJsonPath('checks.reverb.status', 'skipped');

    expect($response->json('checks.database.latency_ms'))->toBeNumeric()->toBeGreaterThanOrEqual(0);
    expect($response->json('checks.cache.latency_ms'))->toBeNumeric()->toBeGreaterThanOrEqual(0);
    expect($response->json('duration_ms'))->toBeNumeric()->toBeGreaterThanOrEqual(0);
});

it('measures R2 with one read-only bucket request and verifies the Reverb protocol', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());
    config([
        'broadcasting.default' => 'reverb',
        'filesystems.disks.r2' => [
            'driver' => 's3', 'key' => 'private-test-key', 'secret' => 'private-test-secret',
            'region' => 'auto', 'bucket' => 'private-test-bucket',
            'endpoint' => 'https://private-storage.example.test', 'retries' => 0,
            'use_path_style_endpoint' => true, 'throw' => true,
        ],
    ]);
    Storage::forgetDisk('r2');
    $handler = new MockHandler([new Result]);
    Storage::disk('r2')->getClient()->getHandlerList()->setHandler($handler);
    $this->mock(ReverbProtocolHealth::class)->shouldReceive('assertHealthy')->once();

    try {
        $response = $this->getJson('/api/diagnostics')->assertOk()
            ->assertJsonPath('checks.storage.status', 'ok')
            ->assertJsonPath('checks.reverb.status', 'ok');
        expect($handler->getLastCommand()->getName())->toBe('HeadBucket');
        expect($handler->getLastCommand()['@retries'])->toBe(0);
        expect($handler->getLastCommand()['@http'])->toMatchArray(['connect_timeout' => 2, 'timeout' => 3]);
        expect($response->getContent())->not->toContain('private-test', 'private-storage.example.test');
    } finally {
        Storage::forgetDisk('r2');
    }
});

it('reports failed dependencies without exposing credentials or failing the remaining probes', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());
    config([
        'broadcasting.default' => 'reverb',
        'filesystems.disks.r2' => [
            'driver' => 's3', 'key' => 'private-test-key', 'secret' => 'private-test-secret',
            'region' => 'auto', 'bucket' => 'private-test-bucket',
            'endpoint' => 'https://private-storage.example.test', 'retries' => 0,
            'use_path_style_endpoint' => true, 'throw' => true,
        ],
    ]);
    Storage::forgetDisk('r2');
    Storage::disk('r2')->getClient()->getHandlerList()->setHandler(new MockHandler([
        new AwsException('private-test-secret', new Command('HeadBucket')),
    ]));
    $this->mock(ReverbProtocolHealth::class)->shouldReceive('assertHealthy')->once()
        ->andThrow(new RuntimeException('private-test-secret'));

    try {
        $response = $this->getJson('/api/diagnostics')->assertOk()
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'ok')
            ->assertJsonPath('checks.storage.status', 'failed')
            ->assertJsonPath('checks.reverb.status', 'failed');
        expect($response->getContent())->not->toContain('private-test', 'private-storage.example.test');
    } finally {
        Storage::forgetDisk('r2');
    }
});

it('continues remote checks when the database probe fails', function (): void {
    $previousConnection = config('database.default');
    config(['database.default' => 'unavailable']);

    try {
        $result = app(ServerDiagnostics::class)->measure();
    } finally {
        config(['database.default' => $previousConnection]);
    }

    expect($result['checks']['database']['status'])->toBe('failed');
    expect($result['checks']['cache']['status'])->toBe('ok');
    expect(json_encode($result))->not->toContain('unavailable');
});

it('reuses dependency samples for fifteen seconds and then measures again', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());
    $this->freezeTime();
    config(['broadcasting.default' => 'reverb']);
    $this->mock(ReverbProtocolHealth::class)->shouldReceive('assertHealthy')->twice();

    $first = $this->getJson('/api/diagnostics')->assertOk();
    $this->travel(14)->seconds();
    $second = $this->getJson('/api/diagnostics')->assertOk();
    $this->travel(2)->seconds();
    $third = $this->getJson('/api/diagnostics')->assertOk();

    expect($second->json('checked_at'))->toBe($first->json('checked_at'));
    expect($third->json('checked_at'))->not->toBe($first->json('checked_at'));
    $first->assertJsonPath('request.probes_cached', false);
    $second->assertJsonPath('request.probes_cached', true);
    $third->assertJsonPath('request.probes_cached', false);
});

it('measures each API request including bearer authentication without reusing cached request timings', function (): void {
    $this->freezeSecond();
    $user = User::factory()->customer()->create();
    $token = $user->createToken('diagnostics-test')->plainTextToken;
    config(['filesystems.disks.r2.driver' => 'local', 'broadcasting.default' => 'null']);

    $first = $this->withToken($token)->getJson('/api/diagnostics')
        ->assertOk()
        ->assertHeaderContains('Server-Timing', 'app;dur=')
        ->assertHeaderContains('Server-Timing', 'db;dur=')
        ->assertJsonPath('request.database_queries', 4)
        ->assertJsonPath('request.probes_cached', false)
        ->assertJsonPath('request.limiter_store', 'array');
    $this->travel(1)->seconds();
    Auth::forgetGuards();
    $second = $this->withToken($token)->getJson('/api/diagnostics')
        ->assertOk()
        ->assertJsonPath('request.database_queries', 3)
        ->assertJsonPath('request.probes_cached', true);

    expect($second->json('checked_at'))->toBe($first->json('checked_at'));
    expect($first->json('request.duration_ms'))->toBeNumeric()->toBeGreaterThanOrEqual(0);
    expect($first->json('request.database_ms'))->toBeNumeric()->toBeGreaterThanOrEqual(0);
    expect($second->json('request.database_ms'))->toBeNumeric()->toBeGreaterThanOrEqual(0);
    expect(request()->attributes->has('diagnostics.sql'))->toBeFalse();
});

it('keeps diagnostic request metrics out of ordinary API responses', function (): void {
    $user = User::factory()->customer()->create();
    $token = $user->createToken('diagnostics-test')->plainTextToken;

    $this->withToken($token)->getJson('/api/diagnostics')->assertOk();
    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/auth/me')
        ->assertOk()
        ->assertHeaderMissing('Server-Timing')
        ->assertJsonMissingPath('request');
});

it('includes the database rate limiter queries in request metrics with fresh and cached samples', function (): void {
    $this->freezeSecond();
    $user = User::factory()->customer()->create();
    $token = $user->createToken('diagnostics-test')->plainTextToken;
    config([
        'cache.default' => 'database',
        'cache.limiter' => 'database',
        'filesystems.disks.r2.driver' => 'local',
        'broadcasting.default' => 'null',
    ]);
    $limit = app(RateLimiter::class)->limiter('diagnostics');
    $limiter = new RateLimiter(Cache::store('database'));
    $limiter->for('diagnostics', $limit);
    $this->app->instance(RateLimiter::class, $limiter);

    $this->withToken($token)->getJson('/api/diagnostics')
        ->assertOk()
        ->assertJsonPath('request.database_queries', 13)
        ->assertJsonPath('request.limiter_store', 'database')
        ->assertJsonPath('request.probes_cached', false);
    $this->travel(1)->seconds();
    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/diagnostics')
        ->assertOk()
        ->assertJsonPath('request.database_queries', 9)
        ->assertJsonPath('request.probes_cached', true);
});

it('limits diagnostic requests per user', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    for ($request = 0; $request < 6; $request++) {
        $this->getJson('/api/diagnostics')->assertOk();
    }

    $this->getJson('/api/diagnostics')->assertTooManyRequests();
});
