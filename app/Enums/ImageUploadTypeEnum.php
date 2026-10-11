<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * @method static JPEG()
 * @method static PNG()
 * @method static GIF()
 * @method static WEBP()
 * @method static AVIF()
 * @method static BMP()
 */
final class ImageUploadTypeEnum
{
    public const string JPEG = 'image/jpeg';

    public const string PNG = 'image/png';

    public const string GIF = 'image/gif';

    public const string WEBP = 'image/webp';

    public const string AVIF = 'image/avif';

    public const string BMP = 'image/bmp';
}
