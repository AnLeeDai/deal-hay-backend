<?php

namespace App\Services;

use App\Enums\ImagePurposeEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\ImageManager;

class BrowserImageService
{
    public const int MAX_PIXEL_COUNT = 32_000_000;

    public function __construct(private readonly ImageManager $manager) {}

    public function prepare(UploadedFile $file, string $purpose): string
    {
        $this->validateSource($file);
        $maximum = $purpose === 'avatar' ? 640 : 1280;
        $manager = new ImageManager($this->manager->driver::class, autoOrientation: false, decodeAnimation: false);

        try {
            $image = $manager->decodeSplFileInfo($file)
                ->removeAnimation()
                ->scaleDown($maximum, $maximum)
                ->orient();
        } catch (ImageDecoderException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'image' => 'Không thể giải mã ảnh tải lên. Tệp có thể bị hỏng hoặc định dạng chưa được hỗ trợ.',
            ]);
        }
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

    private function validateSource(UploadedFile $file): void
    {
        $size = (int) $file->getSize();

        if ($size < 1 || $size > ImageService::MAX_SOURCE_BYTES) {
            throw ValidationException::withMessages(['image' => 'Ảnh tải lên phải có kích thước từ 1 byte đến 30 MB.']);
        }

        $mime = (string) $file->getMimeType();

        if (! in_array($mime, ImagePurposeEnum::Product->inputMimeTypes(), true)) {
            throw ValidationException::withMessages(['image' => 'Ảnh tải lên phải có định dạng JPG, PNG, GIF, WEBP, AVIF hoặc BMP.']);
        }

        $info = @getimagesize($file->getRealPath());

        if (! is_array($info)) {
            throw ValidationException::withMessages(['image' => 'Không thể đọc kích thước hình ảnh tải lên.']);
        }

        if ($info[0] < 1
            || $info[1] < 1
            || $info[0] > ImageService::MAX_DIMENSION
            || $info[1] > ImageService::MAX_DIMENSION
            || $info[0] * $info[1] > self::MAX_PIXEL_COUNT) {
            throw ValidationException::withMessages(['image' => 'Ảnh tải lên không được vượt quá 32 MP và 8192 pixel mỗi chiều.']);
        }

        if (! $this->manager->driver->supports($mime)) {
            throw ValidationException::withMessages(['image' => 'Máy chủ hiện chưa hỗ trợ xử lý định dạng ảnh này. Vui lòng chọn JPG, PNG hoặc WEBP.']);
        }
    }
}
