<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static LockAppeal()
 * @method static NotificationAppeal()
 * @method static ShopOwnerUpgrade()
 */
final class TicketTypeEnum extends Enum
{
    public const string LockAppeal = 'lock_appeal';

    public const string NotificationAppeal = 'notification_appeal';

    public const string ShopOwnerUpgrade = 'shop_owner_upgrade';

    public static function label(string $type): string
    {
        return match ($type) {
            self::LockAppeal => 'Hỗ trợ tài khoản bị khóa',
            self::NotificationAppeal => 'Khiếu nại thông báo',
            self::ShopOwnerUpgrade => 'Đăng ký nâng cấp Chủ Shop',
            default => 'Nhóm hỗ trợ khác',
        };
    }
}
