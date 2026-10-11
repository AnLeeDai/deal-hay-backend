<?php

namespace App\Actions;

use App\Models\User;
use App\Services\UserSessionService;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutUser
{
    public function __construct(private readonly UserSessionService $sessions) {}

    public function handle(?User $user, ?string $sessionId, ?string $plainTextToken = null): void
    {
        if ($user === null) {
            return;
        }

        $currentAccessToken = $user->currentAccessToken();

        if (! $currentAccessToken instanceof PersonalAccessToken && is_string($plainTextToken) && $plainTextToken !== '') {
            $currentAccessToken = PersonalAccessToken::findToken($plainTextToken);
        }

        if ($currentAccessToken instanceof PersonalAccessToken) {
            $currentAccessToken->delete();
        }

        if ($sessionId !== null) {
            $this->sessions->remove($user, $sessionId);
        }
    }
}
