<?php

use App\Services\BrowserImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

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
    $previousLimit = ini_set('memory_limit', '256M');

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
        $content = app(BrowserImageService::class)->prepare($file, $purpose);
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
