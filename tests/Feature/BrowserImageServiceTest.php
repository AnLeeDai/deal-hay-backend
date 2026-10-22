<?php

use App\Services\BrowserImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

function browserCameraPhoto(int $width, int $height, bool $portrait = false): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width - 1, (int) ($height / 2) - 1, 0xFF0000);
    imagefilledrectangle($image, 0, (int) ($height / 2), $width - 1, $height - 1, 0x0000FF);
    ob_start();
    imagejpeg($image, null, 90);
    $content = ob_get_clean();
    unset($image);

    if ($portrait) {
        $exif = "Exif\0\0II\x2a\0\x08\0\0\0\x01\0\x12\x01\x03\0\x01\0\0\0\x06\0\0\0\0\0\0\0";
        $content = substr($content, 0, 2)."\xff\xe1".pack('n', strlen($exif) + 2).$exif.substr($content, 2);
    }

    return UploadedFile::fake()->createWithContent('camera.jpg', $content);
}

it('returns a small correctly oriented WebP for a 24 MP camera JPEG through the browser endpoint', function (): void {
    // The separate-process tests below cover fixed production memory limits.
    $previousLimit = ini_set('memory_limit', (string) (memory_get_usage(true) + 256 * 1024 * 1024));

    try {
        $file = browserCameraPhoto(6000, 4000, portrait: true);
        $token = 'isolated-camera-grant';
        Cache::put('browser-upload:'.hash('sha256', $token), ['size' => $file->getSize(), 'purpose' => 'product'], now()->addMinutes(2));

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
            ->post('/api/media/browser/transform', ['image' => $file]);

        $response->assertOk()->assertHeader('Content-Type', 'image/webp');
        $content = $response->getContent();
        $info = getimagesizefromstring($content);
        expect(strlen($content))->toBeLessThanOrEqual(20 * 1024);
        expect($info['mime'])->toBe('image/webp');
        expect($info[1])->toBeGreaterThan($info[0]);
        expect($info[1])->toBeLessThanOrEqual(1280);
        $image = imagecreatefromstring($content);
        $left = imagecolorat($image, 5, 5);
        $right = imagecolorat($image, imagesx($image) - 6, 5);
        expect($left & 0xFF)->toBeGreaterThan(($left >> 16) & 0xFF);
        expect(($right >> 16) & 0xFF)->toBeGreaterThan($right & 0xFF);
        unset($image);
    } finally {
        ini_set('memory_limit', $previousLimit);
    }
});

it('accepts a 26 MP camera source for every upload purpose', function (string $purpose, int $maximum): void {
    $previousLimit = ini_set('memory_limit', '256M');

    try {
        $file = browserCameraPhoto(6240, 4160);
        // Isolate the API's memory budget from the test runner's own allocations.
        $process = new Process([
            PHP_BINARY, '-d', 'memory_limit=256M', '-r',
            <<<'PHP'
            require $argv[1].'/vendor/autoload.php';
            $application = require $argv[1].'/bootstrap/app.php';
            $application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $file = new Illuminate\Http\UploadedFile($argv[2], 'camera.jpg', 'image/jpeg', null, true);
            echo $application->make(App\Services\BrowserImageService::class)->prepare($file, $argv[3]);
            PHP,
            base_path(),
            $file->getRealPath(),
            $purpose,
        ], env: [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'APP_CONFIG_CACHE' => base_path('bootstrap/cache/config-testing.php'),
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
            'LOG_CHANNEL' => 'null',
        ]);
        $process->mustRun();
        $content = $process->getOutput();
        $info = getimagesizefromstring($content);

        expect($info['mime'])->toBe('image/webp');
        expect(strlen($content))->toBeLessThanOrEqual(20 * 1024);
        expect(max($info[0], $info[1]))->toBeLessThanOrEqual($maximum);
    } finally {
        ini_set('memory_limit', $previousLimit);
    }
})->with(['avatar' => ['avatar', 640], 'product' => ['product', 1280], 'review' => ['review', 1280], 'chat' => ['chat', 1280]]);

it('returns the actual dimension limit as a 422 image field error', function (): void {
    $file = browserCameraPhoto(9000, 100);
    $token = 'isolated-dimension-grant';
    Cache::put('browser-upload:'.hash('sha256', $token), ['size' => $file->getSize(), 'purpose' => 'product'], now()->addMinutes(2));

    $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
        ->post('/api/media/browser/transform', ['image' => $file])
        ->assertUnprocessable()
        ->assertJsonPath('errors.image.0', 'Ảnh tải lên không được vượt quá 32 MP và 8192 pixel mỗi chiều.');
});

it('compresses a detailed 11 MB JPEG directly without rejecting an oversized intermediate WebP', function (): void {
    $previousLimit = ini_set('memory_limit', '256M');

    try {
        $image = imagecreatetruecolor(2400, 2400);
        $seed = 1234567;
        for ($row = 0; $row < 2400; $row++) {
            for ($column = 0; $column < 2400; $column++) {
                $seed = ($seed * 1664525 + 1013904223) & 0xFFFFFFFF;
                imagesetpixel($image, $column, $row, $seed & 0xFFFFFF);
            }
        }
        ob_start();
        imagejpeg($image, null, 100);
        $source = ob_get_clean();
        unset($image);
        $file = UploadedFile::fake()->createWithContent('detailed.jpg', $source);

        $content = app(BrowserImageService::class)->prepare($file, 'product');

        expect($file->getSize())->toBeGreaterThan(10 * 1024 * 1024);
        expect(getimagesizefromstring($content)['mime'])->toBe('image/webp');
        expect(strlen($content))->toBeLessThanOrEqual(20 * 1024);
    } finally {
        ini_set('memory_limit', $previousLimit);
    }
});

it('returns a specific decode error when an image header is valid but the pixel data is missing', function (): void {
    $header = substr(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLbtAAAAABJRU5ErkJggg=='), 0, 33);
    $file = UploadedFile::fake()->createWithContent('broken.png', $header);

    expect(fn () => app(BrowserImageService::class)->prepare($file, 'product'))
        ->toThrow(ValidationException::class, 'Không thể giải mã ảnh tải lên. Tệp có thể bị hỏng hoặc định dạng chưa được hỗ trợ.');
});

it('refuses a source above 32 MP before decoding it', function (): void {
    $previousLimit = ini_set('memory_limit', '256M');

    try {
        $file = browserCameraPhoto(8000, 4001);

        expect(fn () => app(BrowserImageService::class)->prepare($file, 'product'))
            ->toThrow(ValidationException::class, 'Ảnh tải lên không được vượt quá 32 MP và 8192 pixel mỗi chiều.');
    } finally {
        ini_set('memory_limit', $previousLimit);
    }
});

it('returns an image validation error before decoding a source exceeding the available memory', function (): void {
    $header = substr(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLbtAAAAABJRU5ErkJggg=='), 0, 33);
    $header = substr_replace($header, pack('NN', 6000, 4000), 16, 8);
    $file = UploadedFile::fake()->createWithContent('large.png', $header);
    $token = 'isolated-memory-grant';
    Cache::put('browser-upload:'.hash('sha256', $token), ['size' => $file->getSize(), 'purpose' => 'product'], now()->addMinutes(2));
    $previousLimit = ini_set('memory_limit', (string) (memory_get_usage(true) + 16 * 1024 * 1024));

    try {
        $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
            ->post('/api/media/browser/transform', ['image' => $file])
            ->assertUnprocessable()
            ->assertJsonPath('errors.image.0', 'Ảnh cần quá nhiều bộ nhớ để xử lý. Vui lòng giảm kích thước hoặc độ phân giải ảnh rồi thử lại.');
    } finally {
        ini_set('memory_limit', $previousLimit);
    }
});

it('handles real JPEGs safely in a separate 128 MiB API process', function (int $width, int $height, int $status): void {
    $path = tempnam(sys_get_temp_dir(), 'camera-memory-budget-');

    try {
        $producer = new Process([
            PHP_BINARY, '-d', 'memory_limit=256M', '-r',
            <<<'PHP'
            $image = imagecreatetruecolor((int) $argv[2], (int) $argv[3]);
            imagejpeg($image, $argv[1], 90);
            PHP,
            $path,
            (string) $width,
            (string) $height,
        ]);
        $producer->mustRun();

        $consumer = new Process([
            PHP_BINARY, '-d', 'memory_limit=128M', '-r',
            <<<'PHP'
            require $argv[1].'/vendor/autoload.php';
            $application = require $argv[1].'/bootstrap/app.php';
            $kernel = $application->make(Illuminate\Contracts\Http\Kernel::class);
            $kernel->bootstrap();
            $file = new Illuminate\Http\UploadedFile($argv[2], 'camera.jpg', 'image/jpeg', null, true);
            $token = 'isolated-subprocess-grant';
            Illuminate\Support\Facades\Cache::put('browser-upload:'.hash('sha256', $token), ['size' => $file->getSize(), 'purpose' => 'product'], now()->addMinutes(2));
            $request = Illuminate\Http\Request::create('/api/media/browser/transform', 'POST', [], [], ['image' => $file], ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_ACCEPT' => 'application/json']);
            $response = $kernel->handle($request);
            echo json_encode(['status' => $response->getStatusCode(), 'errors' => $response->getStatusCode() === 422 ? json_decode($response->getContent(), true)['errors'] : [], 'content_type' => $response->headers->get('Content-Type'), 'peak_memory' => memory_get_peak_usage(true)], JSON_THROW_ON_ERROR);
            PHP,
            base_path(),
            $path,
        ], env: [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'APP_CONFIG_CACHE' => base_path('bootstrap/cache/config-testing.php'),
            'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
            'LOG_CHANNEL' => 'null',
        ]);
        $consumer->mustRun();
        $result = json_decode($consumer->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        expect($result['status'])->toBe($status);

        if ($status === 422) {
            expect($result['errors']['image'][0])->toBe('Ảnh cần quá nhiều bộ nhớ để xử lý. Vui lòng giảm kích thước hoặc độ phân giải ảnh rồi thử lại.');
        } else {
            expect($result['content_type'])->toBe('image/webp');
        }

        expect($result['peak_memory'])->toBeLessThan(128 * 1024 * 1024);
    } finally {
        unlink($path);
    }
})->with(['camera photo' => [6240, 4160, 422], 'ordinary photo' => [1600, 1200, 200]]);
