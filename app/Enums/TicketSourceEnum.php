<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static LockedAccountDialog()
 * @method static NotificationCenter()
 * @method static UserMenu()
 */
final class TicketSourceEnum extends Enum
{
    public const string LockedAccountDialog = 'locked_account_dialog';

    public const string NotificationCenter = 'notification_center';

    public const string UserMenu = 'user_menu';

    public static function label(string $source): string
    {
        return match ($source) {
            self::LockedAccountDialog => 'Màn đăng nhập - tài khoản bị khóa',
            self::NotificationCenter => 'Trung tâm thông báo',
            self::UserMenu => 'Menu người dùng',
            default => 'Nguồn không xác định',
        };
    }
}
