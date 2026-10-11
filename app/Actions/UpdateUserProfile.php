<?php

namespace App\Actions;

use App\Mail\EmailChangeVerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateUserProfile
{
    private const int EMAIL_CHANGE_CODE_TTL_MINUTES = 10;

    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): void
    {
        $nextEmail = array_key_exists('email', $data)
            ? trim((string) $data['email'])
            : (string) $user->email;
        $isChangingEmail = Str::lower($nextEmail) !== Str::lower((string) $user->email);

        if ($isChangingEmail) {
            $this->verifyEmailChange($user, $nextEmail, trim((string) ($data['email_change_code'] ?? '')));
        }

        $profileData = Arr::only($data, [
            'full_name',
            'phone_number',
            'province',
            'ward',
            'address_detail',
        ]);

        if ($isChangingEmail) {
            Password::deleteToken($user);
            $profileData['email'] = $nextEmail;
        }

        $user->fill($profileData)->save();
    }

    private function verifyEmailChange(User $user, string $newEmail, string $verificationCode): void
    {
        if ($verificationCode === '') {
            $this->sendEmailChangeCode($user, $newEmail);

            throw ValidationException::withMessages([
                'email_change_code' => 'Mã xác thực đã được gửi về email hiện tại. Vui lòng nhập mã để hoàn tất đổi email.',
            ]);
        }

        $this->validateEmailChangeCode($user, $newEmail, $verificationCode);
    }

    private function sendEmailChangeCode(User $user, string $newEmail): void
    {
        $verificationCode = (string) random_int(100000, 999999);
        RateLimiter::clear($this->emailChangeAttemptKey($user));

        Cache::put(
            $this->emailChangeCacheKey($user),
            [
                'new_email' => Str::lower($newEmail),
                'code_hash' => Hash::make($verificationCode),
            ],
            now()->addMinutes(self::EMAIL_CHANGE_CODE_TTL_MINUTES),
        );

        try {
            Mail::to($user->email)->send(new EmailChangeVerificationCodeMail(
                fullName: (string) $user->full_name,
                currentEmail: (string) $user->email,
                newEmail: $newEmail,
                verificationCode: $verificationCode,
                expiresInMinutes: self::EMAIL_CHANGE_CODE_TTL_MINUTES,
            ));
        } catch (Throwable $throwable) {
            report($throwable);

            throw ValidationException::withMessages([
                'email' => 'Không thể gửi mã xác thực tới email hiện tại. Vui lòng thử lại sau.',
            ]);
        }
    }

    private function validateEmailChangeCode(User $user, string $newEmail, string $verificationCode): void
    {
        $attemptKey = $this->emailChangeAttemptKey($user);

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            throw ValidationException::withMessages([
                'email_change_code' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng yêu cầu mã mới.',
            ]);
        }

        $cachedPayload = Cache::get($this->emailChangeCacheKey($user));

        if (! is_array($cachedPayload)) {
            throw ValidationException::withMessages([
                'email_change_code' => 'Mã xác thực không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu mã mới.',
            ]);
        }

        $cachedEmail = (string) ($cachedPayload['new_email'] ?? '');
        $cachedCodeHash = (string) ($cachedPayload['code_hash'] ?? '');

        if ($cachedEmail !== Str::lower($newEmail) || $cachedCodeHash === '' || ! Hash::check($verificationCode, $cachedCodeHash)) {
            RateLimiter::hit($attemptKey, self::EMAIL_CHANGE_CODE_TTL_MINUTES * 60);

            throw ValidationException::withMessages([
                'email_change_code' => 'Mã xác thực không chính xác hoặc đã hết hạn. Vui lòng kiểm tra lại.',
            ]);
        }

        Cache::forget($this->emailChangeCacheKey($user));
        RateLimiter::clear($attemptKey);
    }

    private function emailChangeCacheKey(User $user): string
    {
        return 'profile-email-change:'.$user->id;
    }

    private function emailChangeAttemptKey(User $user): string
    {
        return 'profile-email-change-attempts:'.$user->id;
    }
}
