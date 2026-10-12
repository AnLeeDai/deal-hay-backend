<?php

use Illuminate\Support\Env;
use Laravel\Reverb\Application;
use Laravel\Reverb\Connection;
use Laravel\Reverb\Contracts\WebSocketConnection;
use Laravel\Reverb\Protocols\Pusher\EventHandler;
use Laravel\Reverb\Protocols\Pusher\Managers\ArrayChannelManager;
use Laravel\Reverb\Protocols\Pusher\Server;

beforeEach(function (): void {
    $repository = Env::getRepository();
    $this->previousReverbOrigins = $repository->get('REVERB_ALLOWED_ORIGINS');
    $this->previousFrontendUrl = $repository->get('FRONTEND_URL');
    $repository->set('REVERB_ALLOWED_ORIGINS', ' https://deal-hay.vercel.app , deal-hay-fontend.vercel.app ');
    $repository->set('FRONTEND_URL', 'https://excluded.example.test');
});

afterEach(function (): void {
    $repository = Env::getRepository();
    foreach (['REVERB_ALLOWED_ORIGINS' => $this->previousReverbOrigins, 'FRONTEND_URL' => $this->previousFrontendUrl] as $name => $value) {
        if ($value === null) {
            $repository->clear($name);
        } else {
            $repository->set($name, $value);
        }
    }
});

it('establishes Pusher connections for explicit full URL and hostname origins', function (string $origin): void {
    $message = reverbHandshakeMessage($origin);

    expect($message['event'])->toBe('pusher:connection_established');
    expect(json_decode($message['data'], true)['socket_id'])->toBeString()->not->toBeEmpty();
})->with([
    'canonical frontend URL' => 'https://deal-hay.vercel.app',
    'previous frontend hostname' => 'https://deal-hay-fontend.vercel.app',
]);

it('returns Pusher error 4009 for an origin outside the explicit allowlist', function (): void {
    $message = reverbHandshakeMessage('https://unrelated.example.test');

    expect($message['event'])->toBe('pusher:error');
    expect(json_decode($message['data'], true))->toBe(['code' => 4009, 'message' => 'Origin not allowed']);
});

it('does not append the frontend URL to an explicitly configured origin allowlist', function (): void {
    $message = reverbHandshakeMessage('https://excluded.example.test');

    expect($message['event'])->toBe('pusher:error');
    expect(json_decode($message['data'], true)['code'])->toBe(4009);
});

/** @return array{event: string, data: string} */
function reverbHandshakeMessage(string $origin): array
{
    $configuration = require config_path('reverb.php');
    $application = new Application('test-app', 'test-key', 'test-secret', 30, 60, $configuration['apps']['apps'][0]['allowed_origins'], 10_000);
    $socket = new class implements WebSocketConnection
    {
        /** @var list<string> */
        public array $messages = [];

        public function id(): int
        {
            return 1;
        }

        public function send(mixed $message): void
        {
            $this->messages[] = (string) $message;
        }

        public function close(mixed $message = null): void {}
    };

    $channels = new ArrayChannelManager;
    (new Server($channels, new EventHandler($channels)))->open(new Connection($socket, $application, $origin));

    return json_decode($socket->messages[0], true, flags: JSON_THROW_ON_ERROR);
}
