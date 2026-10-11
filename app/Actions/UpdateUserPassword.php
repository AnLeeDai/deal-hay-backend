<?php

namespace App\Actions;

use App\Models\User;
use App\Services\UserSessionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UpdateUserPassword
{
    public function __construct(private readonly UserSessionService $sessions) {}

    public function handle(User $user, string $password, ?string $currentSessionId): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()->delete();
        $this->sessions->revoke($user, $currentSessionId);
    }
}
