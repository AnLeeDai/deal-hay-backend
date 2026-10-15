<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendPasswordResetLink
{
    /** @param array{email: string} $credentials */
    public function handle(array $credentials): void
    {
        try {
            Password::sendResetLink($credentials, function (User $user, string $token): void {
                try {
                    $user->sendPasswordResetNotification($token);
                } catch (Throwable $throwable) {
                    Password::deleteToken($user);

                    throw $throwable;
                }

                event(new PasswordResetLinkSent($user));
            });
        } catch (Throwable $throwable) {
            report($throwable);

            throw ValidationException::withMessages([
                'email' => 'Không thể gửi email thông tin đăng nhập. Vui lòng thử lại sau.',
            ]);
        }
    }
}
