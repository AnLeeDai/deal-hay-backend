<?php

namespace App\Services;

use Closure;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ServerDiagnostics
{
    public function __construct(private readonly ReverbProtocolHealth $reverb) {}

    /** @return array{checked_at: string, duration_ms: float, checks: array<string, array{status: string, latency_ms: float|null, message: string}>} */
    public function measure(): array
    {
        $startedAt = hrtime(true);
        $checks = [
            'database' => $this->check(fn () => DB::connection()->select('SELECT 1'), 'SELECT 1 qua kết nối database hiện tại.'),
            'cache' => $this->check(fn () => Cache::store()->get('__deal_hay_diagnostics__'), 'Đọc cache dùng chung của ứng dụng.'),
            'storage' => config('filesystems.disks.r2.driver') === 's3'
                ? $this->check(fn () => $this->storage(), 'Kiểm tra bucket R2 bằng HeadBucket; không tải hoặc ghi ảnh.')
                : $this->skipped('Đang dùng kho ảnh cục bộ; chưa cấu hình R2.'),
            'reverb' => config('broadcasting.default') === 'reverb'
                ? $this->check(fn () => $this->reverb->assertHealthy(), 'Kết nối và bắt tay WebSocket từ API tới Reverb.')
                : $this->skipped('Realtime đang tắt.'),
        ];

        return [
            'checked_at' => now()->toISOString(),
            'duration_ms' => $this->elapsed($startedAt),
            'checks' => $checks,
        ];
    }

    private function storage(): void
    {
        foreach (['bucket', 'key', 'secret', 'endpoint'] as $key) {
            if (! filled(config('filesystems.disks.r2.'.$key))) {
                throw new RuntimeException('R2 is not configured.');
            }
        }

        $disk = Storage::disk('r2');
        if (! $disk instanceof AwsS3V3Adapter) {
            throw new RuntimeException('R2 does not use an S3 adapter.');
        }

        $disk->getClient()->headBucket([
            'Bucket' => config('filesystems.disks.r2.bucket'),
            '@retries' => 0,
            '@http' => ['connect_timeout' => 2, 'timeout' => 3],
        ]);
    }

    /** @return array{status: string, latency_ms: float, message: string} */
    private function check(Closure $probe, string $message): array
    {
        $startedAt = hrtime(true);

        try {
            $probe();

            return ['status' => 'ok', 'latency_ms' => $this->elapsed($startedAt), 'message' => $message];
        } catch (Throwable) {
            return [
                'status' => 'failed',
                'latency_ms' => $this->elapsed($startedAt),
                'message' => 'Không hoàn thành phép đo. Dịch vụ có thể chưa cấu hình, không đủ quyền hoặc đang mất kết nối.',
            ];
        }
    }

    /** @return array{status: string, latency_ms: null, message: string} */
    private function skipped(string $message): array
    {
        return ['status' => 'skipped', 'latency_ms' => null, 'message' => $message];
    }

    private function elapsed(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
