<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static Pending()
 * @method static Approved()
 * @method static Rejected()
 */
final class TicketStatusEnum extends Enum
{
    public const string Pending = 'pending';

    public const string Approved = 'approved';

    public const string Rejected = 'rejected';
}
