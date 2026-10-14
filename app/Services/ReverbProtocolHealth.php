<?php

namespace App\Services;

use RuntimeException;

class ReverbProtocolHealth
{
    private const string WEBSOCKET_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /** @param resource $socket */
    public function assertHealthy($socket = null): void
    {
        $ownsSocket = ! is_resource($socket);

        if ($ownsSocket) {
            $socket = $this->connect();
        }

        try {
            $key = base64_encode(random_bytes(16));
            $appKey = (string) config('broadcasting.connections.reverb.key');
            $origin = rtrim((string) config('reverb.health_origin', 'http://localhost'), '/');
            $request = implode("\r\n", [
                sprintf(
                    'GET /app/%s?protocol=7&client=dealhay-health&version=1.0&flash=false HTTP/1.1',
                    rawurlencode($appKey),
                ),
                sprintf('Host: %s:%d', $this->socketHost(), $this->port()),
                'Origin: '.$origin,
                'Upgrade: websocket',
                'Connection: Upgrade',
                'Sec-WebSocket-Key: '.$key,
                'Sec-WebSocket-Version: 13',
                '',
                '',
            ]);

            if (fwrite($socket, $request) !== strlen($request)) {
                throw new RuntimeException('Unable to write the Reverb health handshake.');
            }

            $headers = $this->readHeaders($socket);
            $this->assertUpgradeResponse($headers, $key);
            $this->assertConnectionEstablished($this->readFrame($socket));
        } finally {
            if ($ownsSocket && is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    /** @return resource */
    private function connect()
    {
        $context = stream_context_create([
            'ssl' => [
                'peer_name' => $this->host(),
                'verify_peer' => true,
                'verify_peer_name' => true,
                'SNI_enabled' => true,
            ],
        ]);

        $socket = @stream_socket_client(
            sprintf('%s://%s:%d', $this->usesTls() ? 'tls' : 'tcp', $this->socketHost(), $this->port()),
            $errorCode,
            $errorMessage,
            1,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($socket === false) {
            throw new RuntimeException(sprintf(
                'Reverb is unavailable (%d: %s).',
                $errorCode,
                $errorMessage,
            ));
        }

        stream_set_timeout($socket, 1);

        return $socket;
    }

    /** @param resource $socket */
    private function readHeaders($socket): string
    {
        $headers = '';

        while (! str_ends_with($headers, "\r\n\r\n")) {
            if (strlen($headers) >= 8_192) {
                throw new RuntimeException('Reverb returned oversized handshake headers.');
            }

            $headers .= $this->readExact($socket, 1);
        }

        return $headers;
    }

    private function assertUpgradeResponse(string $headers, string $key): void
    {
        if (! preg_match('/^HTTP\/1\.[01] 101\b/', $headers)) {
            throw new RuntimeException('Reverb rejected the WebSocket health handshake.');
        }

        $expectedAccept = base64_encode(sha1($key.self::WEBSOCKET_GUID, true));
        if (! preg_match('/^Sec-WebSocket-Accept:\s*(.+)\r?$/im', $headers, $matches)
            || ! hash_equals($expectedAccept, trim($matches[1]))) {
            throw new RuntimeException('Reverb returned an invalid WebSocket handshake.');
        }
    }

    /** @param resource $socket */
    private function readFrame($socket): string
    {
        $header = $this->readExact($socket, 2);
        $first = ord($header[0]);
        $second = ord($header[1]);

        if (($first & 0x0F) !== 0x01 || ($second & 0x80) !== 0) {
            throw new RuntimeException('Reverb health response was not an unmasked text frame.');
        }

        $length = $second & 0x7F;
        if ($length === 126) {
            $length = unpack('nlength', $this->readExact($socket, 2))['length'];
        } elseif ($length === 127) {
            throw new RuntimeException('Reverb health response frame is too large.');
        }

        if ($length < 1 || $length > 4_096) {
            throw new RuntimeException('Reverb health response frame length is invalid.');
        }

        return $this->readExact($socket, $length);
    }

    private function assertConnectionEstablished(string $payload): void
    {
        $message = json_decode($payload, true);

        if (! is_array($message)
            || ($message['event'] ?? null) !== 'pusher:connection_established'
            || ! is_string($message['data'] ?? null)) {
            throw new RuntimeException('Reverb did not establish a Pusher connection.');
        }

        $data = json_decode($message['data'], true);
        if (! is_array($data) || ! is_string($data['socket_id'] ?? null)) {
            throw new RuntimeException('Reverb returned invalid Pusher connection data.');
        }
    }

    /** @param resource $socket */
    private function readExact($socket, int $length): string
    {
        $buffer = '';

        while (strlen($buffer) < $length) {
            $chunk = fread($socket, $length - strlen($buffer));

            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('Timed out reading the Reverb health response.');
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }

    private function host(): string
    {
        return trim((string) config('broadcasting.connections.reverb.options.host', '127.0.0.1'), '[]');
    }

    private function socketHost(): string
    {
        $host = $this->host();

        return str_contains($host, ':') ? '['.$host.']' : $host;
    }

    private function port(): int
    {
        return (int) config('broadcasting.connections.reverb.options.port', $this->usesTls() ? 443 : 80);
    }

    private function usesTls(): bool
    {
        return in_array(strtolower((string) config('broadcasting.connections.reverb.options.scheme', 'https')), ['https', 'wss'], true);
    }
}
