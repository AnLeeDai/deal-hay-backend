<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Mail\EmailChangeVerificationCodeMail;
use App\Models\ShopProfileChangeLog;
use App\Models\User;
use App\Notifications\ShopProfileUpdatedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateShopProfile
{
    private const int EMAIL_CHANGE_CODE_TTL_MINUTES = 10;

    public function __construct(private readonly NotificationService $notifications) {}

    /** @param array<string, mixed> $validated */
    public function handle(User $shopOwner, array $validated): void
    {
        $data = array_intersect_key($validated, array_flip([
            'shop_name',
            'shop_email',
            'shop_phone_number',
            'shop_province',
            'shop_ward',
            'shop_address_detail',
        ]));
        $nextEmail = array_key_exists('shop_email', $validated)
            ? trim((string) $validated['shop_email'])
            : (string) $shopOwner->shop_email;
        $isChangingEmail = Str::lower($nextEmail) !== Str::lower((string) $shopOwner->shop_email);

        if ($isChangingEmail && filled($shopOwner->shop_email)) {
            $verificationCode = trim((string) ($validated['email_change_code'] ?? ''));

            if ($verificationCode === '') {
                $this->sendEmailChangeCode($shopOwner, $nextEmail);

                throw ValidationException::withMessages([
                    'email_change_code' => 'Mã xác thực đã được gửi về email shop cũ. Vui lòng nhập mã để hoàn tất đổi email shop.',
                ]);
            }

            $this->validateEmailChangeCode($shopOwner, $nextEmail, $verificationCode);
        }

        if ($isChangingEmail) {
            $data['shop_email'] = $nextEmail;
        }

        $changes = $this->changes($shopOwner, $data);
        $changeLog = DB::transaction(function () use ($changes, $data, $shopOwner): ?ShopProfileChangeLog {
            $shopOwner->fill($data)->save();

            return $changes === [] ? null : ShopProfileChangeLog::query()->create([
                'shop_owner_id' => $shopOwner->getKey(),
                'changed_by' => $shopOwner->getKey(),
                'changes' => $changes,
            ]);
        });

        if ($changeLog instanceof ShopProfileChangeLog) {
            $this->notifyAdministrators($shopOwner->refresh(), $changeLog);
        }
    }

    private function sendEmailChangeCode(User $shopOwner, string $newEmail): void
    {
        $code = (string) random_int(100000, 999999);
        RateLimiter::clear($this->attemptKey($shopOwner));
        Cache::put($this->cacheKey($shopOwner), [
            'new_email' => Str::lower($newEmail),
            'code_hash' => Hash::make($code),
        ], now()->addMinutes(self::EMAIL_CHANGE_CODE_TTL_MINUTES));

        try {
            Mail::to($shopOwner->shop_email)->send(new EmailChangeVerificationCodeMail(
                fullName: (string) ($shopOwner->shop_name ?: $shopOwner->full_name),
                currentEmail: (string) $shopOwner->shop_email,
                newEmail: $newEmail,
                verificationCode: $code,
                expiresInMinutes: self::EMAIL_CHANGE_CODE_TTL_MINUTES,
            ));
        } catch (Throwable $throwable) {
            Cache::forget($this->cacheKey($shopOwner));
            report($throwable);

            throw ValidationException::withMessages([
                'shop_email' => 'Không thể gửi mã xác thực tới email shop cũ. Vui lòng thử lại sau.',
            ]);
        }
    }

    private function validateEmailChangeCode(User $shopOwner, string $newEmail, string $code): void
    {
        $attemptKey = $this->attemptKey($shopOwner);

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            throw ValidationException::withMessages([
                'email_change_code' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng yêu cầu mã mới.',
            ]);
        }

        $payload = Cache::get($this->cacheKey($shopOwner));

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'email_change_code' => 'Mã xác thực không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu mã mới.',
            ]);
        }

        $codeHash = (string) ($payload['code_hash'] ?? '');

        if ((string) ($payload['new_email'] ?? '') !== Str::lower($newEmail)
            || $codeHash === ''
            || ! Hash::check($code, $codeHash)) {
            RateLimiter::hit($attemptKey, self::EMAIL_CHANGE_CODE_TTL_MINUTES * 60);

            throw ValidationException::withMessages([
                'email_change_code' => 'Mã xác thực không chính xác hoặc đã hết hạn. Vui lòng kiểm tra lại.',
            ]);
        }

        Cache::forget($this->cacheKey($shopOwner));
        RateLimiter::clear($attemptKey);
    }

    /** @param array<string, mixed> $data @return array<string, array{label: string, old: mixed, new: mixed}> */
    private function changes(User $shopOwner, array $data): array
    {
        $labels = [
            'shop_name' => 'Tên shop',
            'shop_email' => 'Email shop',
            'shop_phone_number' => 'Số điện thoại shop',
            'shop_province' => 'Tỉnh hoặc thành phố của shop',
            'shop_ward' => 'Phường hoặc xã của shop',
            'shop_address_detail' => 'Địa chỉ chi tiết của shop',
        ];
        $changes = [];

        foreach ($data as $field => $nextValue) {
            $currentValue = $shopOwner->getAttribute($field);

            if ((string) ($currentValue ?? '') !== (string) ($nextValue ?? '')) {
                $changes[$field] = [
                    'label' => $labels[$field] ?? $field,
                    'old' => $currentValue,
                    'new' => $nextValue,
                ];
            }
        }

        return $changes;
    }

    private function notifyAdministrators(User $shopOwner, ShopProfileChangeLog $changeLog): void
    {
        User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->each(function (User $admin) use ($changeLog, $shopOwner): void {
            $notification = new ShopProfileUpdatedNotification($shopOwner, $changeLog);
            $admin->notify($notification);
            $storedNotification = $admin->notifications()->find($notification->id);

            if ($storedNotification !== null) {
                $this->notifications->broadcast($storedNotification);
            }
        });
    }

    private function cacheKey(User $shopOwner): string
    {
        return 'shop-email-change:'.$shopOwner->id;
    }

    private function attemptKey(User $shopOwner): string
    {
        return 'shop-email-change-attempts:'.$shopOwner->id;
    }
}
