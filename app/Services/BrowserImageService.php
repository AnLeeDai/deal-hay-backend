<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\ImageManager;

class BrowserImageService
{
    public function __construct(private readonly ImageService $images, private readonly ImageManager $manager) {}

    public function prepare(UploadedFile $file, string $purpose): string
    {
        try {
            $normalized = $this->images->convertToWebp($file);
            $image = $this->manager->decodeBinary($normalized);
        } catch (ImageDecoderException $exception) {
            throw ValidationException::withMessages(['image' => 'Ảnh không hợp lệ.']);
        }
        $maximum = $purpose === 'avatar' ? 640 : 1280;
        $image->scaleDown($maximum, $maximum);
        for ($attempt = 0; $attempt < 8; $attempt++) {
            foreach ([82, 68, 54] as $quality) {
                $content = (string) $image->encode(new WebpEncoder($quality));
                if (strlen($content) <= 20 * 1024) {
                    return $content;
                }
            }
            if ($image->width() === 1 && $image->height() === 1) {
                break;
            }
            $scale = max(0.5, min(0.85, sqrt(20 * 1024 / strlen($content)) * 0.95));
            $image->scaleDown(max(1, (int) floor($image->width() * $scale)), max(1, (int) floor($image->height() * $scale)));
        }
        throw ValidationException::withMessages(['image' => 'Không thể nén ảnh xuống 20 KB. Vui lòng chọn ảnh khác.']);
    }
}
