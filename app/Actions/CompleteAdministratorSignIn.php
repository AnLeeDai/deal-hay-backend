<?php

namespace App\Actions;

use App\Mail\AdministratorSignInVerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class CompleteAdministratorSignIn
{
    private const int CodeTtlMinutes = 10;

    public function handle(User $administrator, ?string $verificationCode): void
    {
        if ($verificationCode === null || $verificationCode === '') {
            $this->sendCode($administrator);

            throw ValidationException::withMessages([
                'admin_verification_code' => 'Mã xác minh đã được gửi đến email quản trị viên. Vui lòng nhập mã để đăng nhập.',
            ]);
        }

        $this->verifyCode($administrator, $verificationCode);
    }

    private function sendCode(User $administrator): void
    {
        $requestKey = $this->sendAttemptKey($administrator);

        if (RateLimiter::tooManyAttempts($requestKey, 3)) {
            throw ValidationException::withMessages([
                'admin_verification_code' => 'Bạn đã yêu cầu mã quá nhiều lần. Vui lòng thử lại sau.',
            ]);
        }

        $verificationCode = (string) random_int(100000, 999999);
        Cache::put(
            $this->cacheKey($administrator),
            ['code_hash' => Hash::make($verificationCode)],
            now()->addMinutes(self::CodeTtlMinutes),
        );
        RateLimiter::hit($requestKey, self::CodeTtlMinutes * 60);
        RateLimiter::clear($this->verifyAttemptKey($administrator));

        try {
            Mail::to($administrator->email)->send(
                new AdministratorSignInVerificationCodeMail(
                    (string) $administrator->full_name,
                    $verificationCode,
                    self::CodeTtlMinutes,
                ),
            );
        } catch (Throwable $throwable) {
            Cache::forget($this->cacheKey($administrator));
            report($throwable);

            throw ValidationException::withMessages([
                'admin_verification_code' => 'Không thể gửi mã xác minh. Vui lòng thử lại sau.',
            ]);
        }
    }

    private function verifyCode(User $administrator, string $verificationCode): void
    {
        $attemptKey = $this->verifyAttemptKey($administrator);

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            throw ValidationException::withMessages([
                'admin_verification_code' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng yêu cầu mã mới.',
            ]);
        }

        $payload = Cache::get($this->cacheKey($administrator));
        $codeHash = is_array($payload) ? (string) ($payload['code_hash'] ?? '') : '';

        if ($codeHash === '' || ! Hash::check($verificationCode, $codeHash)) {
            RateLimiter::hit($attemptKey, self::CodeTtlMinutes * 60);

            throw ValidationException::withMessages([
                'admin_verification_code' => 'Mã xác minh không chính xác hoặc đã hết hạn.',
            ]);
        }

        Cache::forget($this->cacheKey($administrator));
        RateLimiter::clear($attemptKey);
        RateLimiter::clear($this->sendAttemptKey($administrator));
    }

    private function cacheKey(User $administrator): string
    {
        return 'administrator-sign-in:'.$administrator->getKey();
    }

    private function sendAttemptKey(User $administrator): string
    {
        return 'administrator-sign-in-send:'.$administrator->getKey();
    }

    private function verifyAttemptKey(User $administrator): string
    {
        return 'administrator-sign-in-verify:'.$administrator->getKey();
    }
}
