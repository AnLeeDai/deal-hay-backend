<?php

namespace App\Actions;

use App\Mail\ResetPasswordSuccessMail;
use App\Models\User;
use App\Services\UserSessionService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResetUserPassword
{
    public function __construct(private readonly UserSessionService $sessions) {}

    /**
     * @param  array{email: string, token: string, password: string, password_confirmation?: string}  $credentials
     */
    public function handle(array $credentials): string
    {
        $resetUser = null;
        $status = Password::reset(
            $credentials,
            function (User $user, string $password) use (&$resetUser): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
                $this->sessions->revoke($user);
                event(new PasswordReset($user));
                $resetUser = $user;
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        if ($resetUser !== null) {
            $this->sendSuccessEmail($resetUser);
        }

        return $status;
    }

    private function sendSuccessEmail(User $user): void
    {
        try {
            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
            Mail::to($user->email)->send(new ResetPasswordSuccessMail(
                fullName: $user->full_name,
                email: $user->email,
                signInUrl: $frontendUrl.'/sign-in',
            ));
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}
