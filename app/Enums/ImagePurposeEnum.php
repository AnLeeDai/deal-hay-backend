<?php

namespace App\Enums;

use App\Rules\PreparedWebp;

/**
 * Policies for final browser-processed images. The server validates metadata
 * and stores the original bytes without decoding or transforming pixels.
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
            ImageUploadTypeEnum::WEBP,
        ];
    }

    public function maxKilobytes(): int
    {
        return $this === self::Product ? 512 : 20;
    }

    /** @return list<mixed> */
    public function uploadedFileRules(): array
    {
        return [
            'file',
            'mimetypes:'.implode(',', $this->inputMimeTypes()),
            'max:'.$this->maxKilobytes(),
            new PreparedWebp($this),
        ];
    }
}
