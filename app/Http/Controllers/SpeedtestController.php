<?php

namespace App\Http\Controllers;

use App\Services\SpeedtestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpeedtestController extends Controller
{
    public function __construct(private readonly SpeedtestSession $sessions) {}

    public function session(Request $request): JsonResponse
    {
        return response()->json($this->sessions->issue((string) $request->user()->getKey()), headers: $this->headers($request));
    }

    public function ping(Request $request): Response
    {
        $this->sessions->consume($request->bearerToken());

        return response('', 204, $this->headers($request));
    }

    public function download(Request $request): StreamedResponse
    {
        $bytes = filter_var($request->header('x-speedtest-bytes', $request->query('bytes', 1024 * 1024)), FILTER_VALIDATE_INT);
        if ($bytes === false || $bytes < 1 || $bytes > SpeedtestSession::MAX_DOWNLOAD_BYTES) {
            // Verify the scoped token even when rejecting an invalid byte count.
            $this->sessions->consume($request->bearerToken());
            abort(422, 'Dung lượng tải xuống phải từ 1 byte đến 4 MiB.');
        }
        $this->sessions->consume($request->bearerToken(), $bytes);

        return response()->stream(function () use ($bytes): void {
            $remaining = $bytes;
            while ($remaining > 0) {
                $chunk = min(64 * 1024, $remaining);
                echo random_bytes($chunk);
                $remaining -= $chunk;
                flush();
            }
        }, 200, [
            ...$this->headers($request), 'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) $bytes, 'Content-Encoding' => 'identity',
            'X-Speedtest-Bytes' => (string) $bytes, 'Vary' => 'X-Speedtest-Bytes',
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $length = $request->headers->get('content-length');
        $declared = $length !== null ? filter_var($length, FILTER_VALIDATE_INT) : null;
        $safeLength = is_int($declared) && $declared >= 0 && $declared <= SpeedtestSession::MAX_UPLOAD_BYTES;
        // Reserve the full bounded read before trusting a claimed body length.
        $this->sessions->consume($request->bearerToken(), SpeedtestSession::MAX_UPLOAD_BYTES);
        abort_if($length !== null && ! $safeLength, 413, 'Dung lượng tải lên tối đa là 2 MiB.');
        abort_unless(strtolower(trim(explode(';', (string) $request->header('content-type'))[0])) === 'application/octet-stream', 415, 'Vui lòng gửi dữ liệu đo dạng application/octet-stream.');
        $stream = $request->getContent(asResource: true);
        $received = 0;
        try {
            while (! feof($stream) && $received <= SpeedtestSession::MAX_UPLOAD_BYTES) {
                $chunk = fread($stream, min(64 * 1024, SpeedtestSession::MAX_UPLOAD_BYTES + 1 - $received));
                if ($chunk === false) {
                    abort(400, 'Không đọc được dữ liệu tải lên.');
                }
                $received += strlen($chunk);
            }
        } finally {
            fclose($stream);
        }
        abort_if($received > SpeedtestSession::MAX_UPLOAD_BYTES, 413, 'Dung lượng tải lên tối đa là 2 MiB.');
        abort_if($safeLength && $received !== $declared, 400, 'Số byte tải lên không khớp với Content-Length.');

        return response()->json(['received_bytes' => $received], headers: [
            ...$this->headers($request), 'X-Speedtest-Bytes' => (string) $received,
        ]);
    }

    private function headers(Request $request): array
    {
        $headers = ['Cache-Control' => 'no-store, no-cache, max-age=0, must-revalidate, no-transform', 'Pragma' => 'no-cache', 'Expires' => '0'];
        $origin = $request->header('origin');
        if ($origin && (in_array($origin, config('cors.allowed_origins', []), true) || in_array('*', config('cors.allowed_origins', []), true))) {
            $headers['Timing-Allow-Origin'] = $origin;
        }

        return $headers;
    }
}
