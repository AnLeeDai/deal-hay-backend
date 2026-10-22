<?php

use App\Models\User;
use App\Services\ReverbProtocolHealth;
use App\Services\ServerDiagnostics;
use Aws\Command;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
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
});

it('limits diagnostic requests per user', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    for ($request = 0; $request < 6; $request++) {
        $this->getJson('/api/diagnostics')->assertOk();
    }

    $this->getJson('/api/diagnostics')->assertTooManyRequests();
});
