<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;

class NotifyAdministratorsAboutNewUser
{
    public function handle(User $registeredUser): void
    {
        User::query()
            ->where('role', UserRoleEnum::ADMINISTRATOR)
            ->each(function (User $administrator) use ($registeredUser): void {
                $registeredNotification = new NewUserRegisteredNotification($registeredUser);
                $administrator->notify($registeredNotification);
            });
    }
}
