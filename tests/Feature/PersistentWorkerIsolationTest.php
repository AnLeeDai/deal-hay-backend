<?php

use App\Models\User;
use App\Services\UserSessionService;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\CurrentApplication;
use Laravel\Octane\Testing\Fakes\FakeClient;
use Laravel\Octane\Testing\Fakes\FakeWorker;

it('isolates bearer identities and request context across requests in one Octane worker', function (): void {
    $first = User::factory()->customer()->create();
    $second = User::factory()->customer()->create();
    $requestIds = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];
    $tokens = [$first->createToken('first')->plainTextToken, $second->createToken('second')->plainTextToken, null];
    $requests = [];

    foreach ($tokens as $index => $token) {
        $requests[] = Request::create('/api/private/profile/me', 'GET', server: array_filter([
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => $token === null ? null : 'Bearer '.$token,
            'HTTP_X_REQUEST_ID' => $requestIds[$index],
        ]));
    }

    $factory = Mockery::mock(ApplicationFactory::class, [base_path()])->makePartial();
    $factory->warm($this->app);
    $factory->shouldReceive('createApplication')->once()->andReturn($this->app);
    $worker = new FakeWorker($factory, $client = new FakeClient($requests));
    $contexts = [];
    $worker->onRequestHandled(function () use (&$contexts): void {
        $contexts[] = [Context::get('request_id'), Context::get('previous_user')];
        Context::add('previous_user', 'private context from the previous request');
    });

    $worker->boot();
    $worker->run();

    expect($client->errors)->toBeEmpty()
        ->and($client->responses)->toHaveCount(3)
        ->and($client->responses[0]->getStatusCode())->toBe(200)
        ->and($client->responses[0]->getData(true)['auth']['user']['id'])->toBe($first->getKey())
        ->and($client->responses[1]->getStatusCode())->toBe(200)
        ->and($client->responses[1]->getData(true)['auth']['user']['id'])->toBe($second->getKey())
        ->and($client->responses[2]->getStatusCode())->toBe(401)
        ->and($contexts)->toBe([
            [$requestIds[0], null],
            [$requestIds[1], null],
            [$requestIds[2], null],
        ]);

    foreach ($client->responses as $index => $response) {
        expect($response->headers->get('X-Request-ID'))->toBe($requestIds[$index]);
    }
});

it('records query timing on the current container request instead of a provider boot request', function (): void {
    $root = $this->app;
    $originalRequest = Request::create('/original');
    $originalRequest->attributes->set('diagnostics.sql', ['queries' => 0, 'milliseconds' => 0.0]);
    $root->instance('request', $originalRequest);
    $sandbox = clone $root;
    $currentRequest = Request::create('/current');
    $currentRequest->attributes->set('diagnostics.sql', ['queries' => 0, 'milliseconds' => 0.0]);
    $sandbox->instance('request', $currentRequest);

    try {
        CurrentApplication::set($sandbox);
        Event::dispatch(new QueryExecuted('select 1', [], 2.5, DB::connection()));

        expect($currentRequest->attributes->get('diagnostics.sql'))->toBe(['queries' => 1, 'milliseconds' => 2.5])
            ->and($originalRequest->attributes->get('diagnostics.sql'))->toBe(['queries' => 0, 'milliseconds' => 0.0]);
    } finally {
        CurrentApplication::set($root);
    }
});

it('resolves the current session service when a login event is dispatched', function (): void {
    $user = User::factory()->customer()->make();
    $request = Request::create('/login', 'POST');
    $session = new Store('worker-test', new ArraySessionHandler(120));
    $request->setLaravelSession($session);
    $this->app->instance('request', $request);
    $this->mock(UserSessionService::class)
        ->shouldReceive('markCurrent')->once()->with($user, $session->getId())->andReturn(4);

    Event::dispatch(new Login('web', $user, false));

    expect($session->get(UserSessionService::VERSION_KEY))->toBe(4);
});
