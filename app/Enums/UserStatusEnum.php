<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static INACTIVE()
 * @method static ACTIVE()
 */
final class UserStatusEnum extends Enum
{
    public const int INACTIVE = 0;

    public const int ACTIVE = 1;
}
