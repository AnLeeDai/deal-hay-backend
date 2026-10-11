<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Mail\AccountStatusChangedMail;
use App\Models\User;
use App\Services\UserSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ToggleUserLock
{
    public function __construct(private readonly UserSessionService $sessions) {}

    /** @param array{action: string, lock_reason?: string|null} $data */
    public function handle(User $user, array $data): bool
    {
        if ($user->role === UserRoleEnum::ADMINISTRATOR) {
            return false;
        }

        if ($data['action'] === 'lock') {
            DB::transaction(function () use ($data, $user): void {
                $user->forceFill([
                    'is_active' => UserStatusEnum::INACTIVE,
                    'lock_reason' => $data['lock_reason'],
                    'remember_token' => Str::random(60),
                ])->save();
                $this->sessions->revoke($user);
                $user->tokens()->delete();
            });
        } else {
            $user->update(['is_active' => UserStatusEnum::ACTIVE, 'lock_reason' => null]);
        }

        $this->sendMail($user->refresh());

        return true;
    }

    private function sendMail(User $user): void
    {
        try {
            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
            Mail::to($user->email)->send(new AccountStatusChangedMail(
                fullName: $user->full_name,
                email: $user->email,
                isActive: (int) $user->is_active === UserStatusEnum::ACTIVE,
                lockReason: $user->lock_reason,
                signInUrl: $frontendUrl.'/sign-in',
            ));
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}
