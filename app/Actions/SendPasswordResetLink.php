<?php

namespace App\Actions;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendPasswordResetLink
{
    /** @param array{email: string} $credentials */
    public function handle(array $credentials): void
    {
        try {
            Password::sendResetLink($credentials);
        } catch (Throwable $throwable) {
            report($throwable);

            throw ValidationException::withMessages([
                'email' => 'Không thể gửi email thông tin đăng nhập. Vui lòng thử lại sau.',
            ]);
        }
    }
}
