<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    public function __construct(private readonly User $users) {}

    /** @param array{email: string, password: string} $credentials */
    public function handle(array $credentials): User
    {
        $candidate = $this->users
            ->query()
            ->where('email', $credentials['email'])
            ->first();

        if ($candidate === null || ! Hash::check($credentials['password'], $candidate->password)) {
            throw ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không chính xác.',
            ]);
        }

        return $candidate;
    }
}
