<?php

namespace App\Actions;

use App\Models\User;

class IssueUserAccessToken
{
    public function handle(User $user, string $clientName): string
    {
        return $user->createToken($user->resolveDeviceName($clientName), ['*'])->plainTextToken;
    }
}
