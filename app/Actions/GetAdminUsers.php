<?php

namespace App\Actions;

use App\Models\User;
use App\Services\UserPresenceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class GetAdminUsers
{
    public function __construct(private readonly UserPresenceService $presence) {}

    /** @return LengthAwarePaginator<int, User> */
    public function handle(): LengthAwarePaginator
    {
        return User::query()
            ->select([
                'id', 'role', 'user_code', 'full_name', 'email', 'phone_number', 'province', 'ward',
                'address_detail', 'avatar_url', 'is_active', 'lock_reason', 'created_at', 'updated_at',
            ])
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(fn (User $user): array => $this->payload($user));
    }

    /** @return array<string, mixed> */
    private function payload(User $user): array
    {
        $serialized = $user->toArray();
        $lastActivity = $this->presence->lastSeenAt((string) $user->getKey());
        $isOnline = $this->presence->isOnline((string) $user->getKey());

        return [
            'id' => $user->id,
            'role' => $user->role,
            'user_code' => $user->user_code,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'province' => $user->province,
            'ward' => $user->ward,
            'address_detail' => $user->address_detail,
            'avatar_url' => $serialized['avatar_url'] ?? null,
            'is_active' => (bool) $user->is_active,
            'lock_reason' => $user->lock_reason,
            'online_status' => $lastActivity === null ? [
                'is_online' => false,
                'label' => 'Chưa hoạt động',
                'last_activity_at' => null,
            ] : [
                'is_online' => $isOnline,
                'label' => $isOnline ? 'Đang online' : 'Hoạt động '.Carbon::createFromTimestamp($lastActivity)->diffForHumans(),
                'last_activity_at' => $lastActivity,
            ],
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }
}
