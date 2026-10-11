<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use App\Services\NotificationService;

class NotifyAdministratorsAboutNewUser
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(User $registeredUser): void
    {
        User::query()
            ->where('role', UserRoleEnum::ADMINISTRATOR)
            ->each(function (User $administrator) use ($registeredUser): void {
                $registeredNotification = new NewUserRegisteredNotification($registeredUser);
                $administrator->notify($registeredNotification);

                $notification = $administrator->notifications()->find($registeredNotification->id);

                if ($notification !== null) {
                    $this->notifications->broadcast($notification);
                }
            });
    }
}
