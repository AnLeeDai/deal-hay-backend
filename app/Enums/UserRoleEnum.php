<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static ADMINISTRATOR()
 * @method static SHOP_OWNER()
 * @method static CUSTOMER()
 */
final class UserRoleEnum extends Enum
{
    public const string ADMINISTRATOR = 'administrator';

    public const string SHOP_OWNER = 'shop_owner';

    public const string CUSTOMER = 'customer';
}
