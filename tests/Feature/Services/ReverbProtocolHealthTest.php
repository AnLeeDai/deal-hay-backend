<?php

use App\Services\ReverbProtocolHealth;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    config([
        'broadcasting.connections.reverb.key' => 'publisher-key',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'reverb.apps.apps.0.key' => 'different-local-server-key',
        'reverb.servers.reverb.host' => '192.0.2.1',
        'reverb.servers.reverb.port' => 1,
        'reverb.health_origin' => 'https://frontend.example.test/',
    ]);
});

it('checks the publishing endpoint and credentials when the local server binds elsewhere', function (): void {
    [$server, $port] = startReverbHealthServer();
    config(['broadcasting.connections.reverb.options.port' => $port]);

    try {
        app(ReverbProtocolHealth::class)->assertHealthy();
        $server->wait();

        $request = base64_decode(explode("\n", trim($server->getOutput()))[1], true);
        expect($request)->toContain(
            'GET /app/publisher-key?protocol=7&client=dealhay-health&version=1.0&flash=false HTTP/1.1',
            "Host: 127.0.0.1:{$port}\r\n",
            "Origin: https://frontend.example.test\r\n",
        );
    } finally {
        $server->stop(0);
    }
});

it('rejects a remote endpoint without a valid WebSocket and Pusher handshake', function (string $response, string $message): void {
    [$server, $port] = startReverbHealthServer($response);
    config(['broadcasting.connections.reverb.options.port' => $port]);

    try {
        expect(fn () => app(ReverbProtocolHealth::class)->assertHealthy())
            ->toThrow(RuntimeException::class, $message);
    } finally {
        $server->stop(0);
    }
})->with([
    'invalid WebSocket accept key' => ['invalid-accept', 'Reverb returned an invalid WebSocket handshake.'],
    'origin rejected with Pusher error 4009' => ['origin-rejected', 'Reverb did not establish a Pusher connection.'],
]);

it('rejects an untrusted TLS certificate on a secure publishing endpoint', function (string $scheme): void {
    $certificatePath = createReverbHealthCertificate();
    $server = null;

    try {
        [$server, $port] = startReverbHealthServer(certificatePath: $certificatePath);
        config([
            'broadcasting.connections.reverb.options.host' => 'localhost',
            'broadcasting.connections.reverb.options.port' => $port,
            'broadcasting.connections.reverb.options.scheme' => $scheme,
        ]);

        expect(fn () => app(ReverbProtocolHealth::class)->assertHealthy())
            ->toThrow(RuntimeException::class, 'Reverb is unavailable');
    } finally {
        $server?->stop(0);
        unlink($certificatePath);
    }
})->with(['https', 'wss']);

it('establishes a verified TLS connection to a secure publishing endpoint', function (string $scheme): void {
    $certificatePath = createReverbHealthCertificate();
    $server = null;

    try {
        [$server, $port] = startReverbHealthServer(certificatePath: $certificatePath);
        $client = new Process([
            PHP_BINARY,
            '-d',
            'openssl.cafile='.$certificatePath,
            '-r',
            <<<'PHP'
            require $argv[1].'/vendor/autoload.php';
            $application = require $argv[1].'/bootstrap/app.php';
            $application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            config([
                'broadcasting.connections.reverb.key' => 'publisher-key',
                'broadcasting.connections.reverb.options.host' => 'localhost',
                'broadcasting.connections.reverb.options.port' => (int) $argv[2],
                'broadcasting.connections.reverb.options.scheme' => $argv[3],
                'reverb.health_origin' => 'https://frontend.example.test',
            ]);
            $application->make(App\Services\ReverbProtocolHealth::class)->assertHealthy();
            echo 'healthy';
            PHP,
            base_path(),
            (string) $port,
            $scheme,
        ]);
        $client->setTimeout(10);

        $client->mustRun();

        expect($client->getOutput())->toBe('healthy');
    } finally {
        $server?->stop(0);
        unlink($certificatePath);
    }
})->with(['https', 'wss']);

function createReverbHealthCertificate(): string
{
    $certificatePath = tempnam(sys_get_temp_dir(), 'reverb-health-cert-');
    $privateKey = openssl_pkey_new(['private_key_bits' => 2048]);
    $request = openssl_csr_new(['commonName' => 'localhost'], $privateKey);
    $certificate = openssl_csr_sign($request, null, $privateKey, 1);
    openssl_x509_export($certificate, $certificatePem);
    openssl_pkey_export($privateKey, $privateKeyPem);
    file_put_contents($certificatePath, $certificatePem.$privateKeyPem);

    return $certificatePath;
}

/** @return array{Process, int} */
function startReverbHealthServer(string $response = 'established', ?string $certificatePath = null): array
{
    $server = new Process([
        PHP_BINARY,
        '-r',
        <<<'PHP'
        $response = $argv[1];
        $certificatePath = $argv[2];
        $context = stream_context_create($certificatePath === '' ? [] : [
            'ssl' => ['local_cert' => $certificatePath, 'verify_peer' => false],
        ]);
        $transport = $certificatePath === '' ? 'tcp' : 'tls';
        $listener = @stream_socket_server("{$transport}://127.0.0.1:0", $errorCode, $errorMessage, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        if ($listener === false) {
            fwrite(STDERR, "Unable to bind the test Reverb listener: {$errorCode} {$errorMessage}");
            exit(1);
        }
        echo stream_socket_get_name($listener, false), "\n";
        flush();
        $socket = @stream_socket_accept($listener, 5);
        if ($socket === false) {
            fclose($listener);
            exit($certificatePath === '' ? 1 : 0);
        }
        stream_set_timeout($socket, 5);
        $request = '';
        while (! str_ends_with($request, "\r\n\r\n")) {
            $line = fgets($socket);
            if ($line === false) {
                fclose($socket);
                fclose($listener);
                exit(1);
            }
            $request .= $line;
        }
        echo base64_encode($request), "\n";
        preg_match('/^Sec-WebSocket-Key:\s*(.+)\r?$/im', $request, $matches);
        $accept = $response === 'invalid-accept'
            ? 'incorrect-accept-key'
            : base64_encode(sha1(trim($matches[1]).'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $headers = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: {$accept}\r\n\r\n";
        $payload = json_encode($response === 'origin-rejected' ? [
            'event' => 'pusher:error',
            'data' => json_encode(['code' => 4009, 'message' => 'Origin not allowed']),
        ] : [
            'event' => 'pusher:connection_established',
            'data' => json_encode(['socket_id' => '123.456', 'activity_timeout' => 30]),
        ]);
        $frame = chr(0x81).(strlen($payload) < 126 ? chr(strlen($payload)) : chr(126).pack('n', strlen($payload))).$payload;
        fwrite($socket, $headers.$frame);
        fclose($socket);
        fclose($listener);
        PHP,
        $response,
        $certificatePath ?? '',
    ]);
    $server->setTimeout(10);
    $server->start();

    try {
        $ready = $server->waitUntil(fn (string $type, string $output): bool => $type === Process::OUT && str_contains($server->getOutput(), "\n"));

        if (! $ready) {
            throw new RuntimeException($server->getErrorOutput());
        }

        $address = trim(explode("\n", $server->getOutput())[0]);

        return [$server, (int) substr($address, strrpos($address, ':') + 1)];
    } catch (Throwable $exception) {
        $server->stop(0);

        throw $exception;
    }
}
