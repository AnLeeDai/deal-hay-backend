<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\MediaClient;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterUser
{
    public function __construct(
        private readonly User $users,
        private readonly MediaClient $media,
        private readonly NotifyAdministratorsAboutNewUser $notifyAdministrators,
    ) {}

    /** @param array{email: string, password: string} $data */
    public function handle(array $data): User
    {
        $user = $this->createCustomer($data);

        $this->notifyAdministrators->handle($user);

        return $user;
    }

    /** @param array{email: string, password: string} $data */
    private function createCustomer(array $data): User
    {
        $email = Str::lower(trim($data['email']));
        $fullName = Str::of(Str::before($email, '@'))
            ->replace(['.', '_', '-'], ' ')
            ->squish()
            ->title()
            ->value();

        $avatar = $this->media->defaultAvatarPath();

        try {
            return DB::transaction(fn (): User => $this->users->query()->create([
                'role' => UserRoleEnum::CUSTOMER,
                'user_code' => $this->users->generateUserCode(UserRoleEnum::CUSTOMER),
                'full_name' => $fullName,
                'email' => $email,
                'phone_number' => null,
                'province' => null,
                'ward' => null,
                'address_detail' => null,
                'avatar_url' => $avatar,
                'password' => $data['password'],
                'is_active' => UserStatusEnum::ACTIVE,
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => 'Email này đã được sử dụng.',
            ]);
        }
    }
}
