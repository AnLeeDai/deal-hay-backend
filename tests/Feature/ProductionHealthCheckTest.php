<?php

use App\Services\OutboxWorkerHealth;
use App\Services\ReverbProtocolHealth;
use Illuminate\Cache\Repository;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    config()->set('app.debug', false);
});

it('keeps the production API healthy when the outbox worker and Reverb are unavailable', function (): void {
    $this->app->instance('env', 'production');
    config()->set('broadcasting.default', 'reverb');
    $this->mock(ReverbProtocolHealth::class)->shouldNotReceive('assertHealthy');

    $this->getJson('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'up']);
});

it('returns 500 from the production health check when a web dependency fails', function (string $dependency): void {
    $this->app->instance('env', 'production');
    $configurationKey = $dependency === 'database' ? 'database.default' : 'cache.default';
    $previousValue = config($configurationKey);
    config()->set($configurationKey, 'unavailable');

    try {
        $response = $this->getJson('/up');
    } finally {
        config()->set($configurationKey, $previousValue);
    }

    $response->assertInternalServerError()->assertExactJson(['status' => 'down']);
})->with(['database', 'cache']);

it('returns 200 with every readiness check healthy even outside production', function (): void {
    config()->set('broadcasting.default', 'reverb');
    app(OutboxWorkerHealth::class)->record();
    $this->mock(ReverbProtocolHealth::class)
        ->shouldReceive('assertHealthy')->once()->andReturnNull();

    $this->get('/ready')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertHeaderContains('Cache-Control', 'no-store')
        ->assertExactJson([
            'status' => 'ok',
            'checks' => [
                'database' => 'ok',
                'cache' => 'ok',
                'outbox_worker' => 'ok',
                'reverb' => 'ok',
            ],
        ]);
});

it('returns 503 with a sanitized readiness result when the worker has no heartbeat', function (): void {
    config()->set('broadcasting.default', 'reverb');
    $this->mock(ReverbProtocolHealth::class)
        ->shouldReceive('assertHealthy')->once()->andReturnNull();

    $this->get('/ready')
        ->assertServiceUnavailable()
        ->assertHeaderContains('Cache-Control', 'no-store')
        ->assertExactJson([
            'status' => 'degraded',
            'checks' => [
                'database' => 'ok',
                'cache' => 'ok',
                'outbox_worker' => 'failed',
                'reverb' => 'ok',
            ],
        ]);
});

it('returns 503 without exposing private connection details when Reverb fails', function (): void {
    config()->set('broadcasting.default', 'reverb');
    app(OutboxWorkerHealth::class)->record();
    $this->mock(ReverbProtocolHealth::class)
        ->shouldReceive('assertHealthy')->once()
        ->andThrow(new RuntimeException('Private connection credentials: secret@example.internal'));

    $this->getJson('/ready')
        ->assertServiceUnavailable()
        ->assertExactJson([
            'status' => 'degraded',
            'checks' => [
                'database' => 'ok',
                'cache' => 'ok',
                'outbox_worker' => 'ok',
                'reverb' => 'failed',
            ],
        ]);
});

it('returns 503 and continues the remaining readiness checks when a web dependency fails', function (string $dependency): void {
    config()->set('broadcasting.default', 'reverb');
    app(OutboxWorkerHealth::class)->record();
    $this->mock(ReverbProtocolHealth::class)
        ->shouldReceive('assertHealthy')->once()->andReturnNull();
    $configurationKey = $dependency === 'database' ? 'database.default' : 'cache.default';
    $previousValue = config($configurationKey);
    config()->set($configurationKey, 'unavailable');

    try {
        $response = $this->getJson('/ready');
    } finally {
        config()->set($configurationKey, $previousValue);
    }

    $response->assertServiceUnavailable()->assertExactJson([
        'status' => 'degraded',
        'checks' => [
            'database' => $dependency === 'database' ? 'failed' : 'ok',
            'cache' => $dependency === 'cache' ? 'failed' : 'ok',
            'outbox_worker' => $dependency === 'cache' ? 'failed' : 'ok',
            'reverb' => 'ok',
        ],
    ]);
})->with(['database', 'cache']);

it('skips the Reverb readiness check when another broadcaster is configured', function (): void {
    config()->set('broadcasting.default', 'log');
    app(OutboxWorkerHealth::class)->record();
    $this->mock(ReverbProtocolHealth::class)->shouldNotReceive('assertHealthy');

    $this->getJson('/ready')
        ->assertOk()
        ->assertJsonPath('checks.reverb', 'skipped');
});

it('keeps a worker ready while a job runs within its 60 second timeout', function (): void {
    $this->freezeTime();
    app(OutboxWorkerHealth::class)->record();
    $this->travel(60)->seconds();

    $this->getJson('/ready')
        ->assertOk()
        ->assertJsonPath('checks.outbox_worker', 'ok');
});

it('returns 503 when a worker heartbeat is older than the allowed job and restart margin', function (): void {
    $this->freezeTime();
    app(OutboxWorkerHealth::class)->record();
    $this->travel(121)->seconds();

    $this->getJson('/ready')
        ->assertServiceUnavailable()
        ->assertJsonPath('checks.outbox_worker', 'failed');
});

it('records heartbeats only for the configured realtime queue on the outbox connection', function (string $connection, string $queues, bool $expectedHealthy): void {
    config()->set('queue.connections.database_outbox.queue', 'custom-realtime');

    Event::dispatch(new Looping($connection, $queues));

    $response = $this->getJson('/ready');
    if ($expectedHealthy) {
        $response->assertOk();
    } else {
        $response->assertServiceUnavailable();
    }
    $response->assertJsonPath('checks.outbox_worker', $expectedHealthy ? 'ok' : 'failed');
})->with([
    'matching outbox queue' => ['database_outbox', 'images,custom-realtime,default', true],
    'another connection' => ['database', 'custom-realtime', false],
    'another queue' => ['database_outbox', 'images,default', false],
    'queue name is only a prefix' => ['database_outbox', 'custom-realtime-other', false],
]);

it('keeps the queue loop running when its heartbeat cache is unavailable', function (): void {
    config()->set('cache.default', 'unavailable');

    Event::dispatch(new Looping('database_outbox', 'realtime'));

    config()->set('cache.default', 'array');
    $this->getJson('/ready')
        ->assertServiceUnavailable()
        ->assertJsonPath('checks.outbox_worker', 'failed');
});

it('writes at most one worker heartbeat per thirty seconds across queue loops', function (): void {
    $this->freezeTime();
    $timestamp = now()->getTimestamp();
    $repository = Mockery::mock(Repository::class);
    $repository->shouldReceive('put')->once()
        ->with('dealhay:health:outbox-worker', $timestamp, 180)->andReturnTrue();
    $repository->shouldReceive('put')->once()
        ->with('dealhay:health:outbox-worker', $timestamp + 30, 180)->andReturnTrue();
    Cache::shouldReceive('store')->withNoArgs()->twice()->andReturn($repository);

    Event::dispatch(new Looping('database_outbox', 'realtime,images,default'));
    $this->travel(29)->seconds();
    Event::dispatch(new Looping('database_outbox', 'realtime,images,default'));
    $this->travel(1)->seconds();
    Event::dispatch(new Looping('database_outbox', 'realtime,images,default'));
});

it('retries a failed heartbeat on the next queue loop without waiting thirty seconds', function (bool $throws): void {
    $this->freezeTime();
    $attempts = 0;
    $repository = Mockery::mock(Repository::class);
    $repository->shouldReceive('put')->twice()->andReturnUsing(function () use (&$attempts, $throws): bool {
        $attempts++;

        if ($attempts === 1) {
            if ($throws) {
                throw new RuntimeException('Heartbeat cache unavailable.');
            }

            return false;
        }

        return true;
    });
    Cache::shouldReceive('store')->withNoArgs()->twice()->andReturn($repository);

    Event::dispatch(new Looping('database_outbox', 'realtime'));
    Event::dispatch(new Looping('database_outbox', 'realtime'));
    Event::dispatch(new Looping('database_outbox', 'realtime'));

    expect($attempts)->toBe(2);
})->with(['exception' => [true], 'unsuccessful write' => [false]]);
