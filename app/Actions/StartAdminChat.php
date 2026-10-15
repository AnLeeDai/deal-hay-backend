<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ChatConversation;
use App\Models\User;

class StartAdminChat
{
    public function handle(User $administrator, User $recipient): ChatConversation
    {
        abort_unless(
            $administrator->role === UserRoleEnum::ADMINISTRATOR
            && (bool) $administrator->is_active
            && $administrator->getKey() !== $recipient->getKey(),
            403,
        );

        return ChatConversation::query()->firstOrCreate([
            'buyer_id' => $administrator->getKey(),
            'shop_owner_id' => $recipient->getKey(),
        ]);
    }
}
