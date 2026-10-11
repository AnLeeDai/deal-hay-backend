<?php

namespace App\Enums;

/**
 * One source of truth for image input policies. Every image still passes
 * through ImageService before storage; these profiles only express the
 * purpose-specific browser/request limits around that shared normalizer.
 */
enum ImagePurposeEnum: string
{
    case Avatar = 'avatar';
    case Product = 'product';
    case Review = 'review';
    case Chat = 'chat';

    /** @return list<string> */
    public function inputMimeTypes(): array
    {
        return [
            ImageUploadTypeEnum::JPEG,
            ImageUploadTypeEnum::PNG,
            ImageUploadTypeEnum::GIF,
            ImageUploadTypeEnum::WEBP,
            ImageUploadTypeEnum::AVIF,
            ImageUploadTypeEnum::BMP,
        ];
    }

    public function maxKilobytes(): int
    {
        return 30_720;
    }

    /** @return list<string> */
    public function uploadedFileRules(): array
    {
        return [
            'file',
            'image',
            'mimetypes:'.implode(',', $this->inputMimeTypes()),
            'max:'.$this->maxKilobytes(),
        ];
    }
}
