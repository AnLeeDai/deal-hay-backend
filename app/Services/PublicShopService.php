<?php

namespace App\Services;

use App\Enums\UserRoleEnum;
use App\Models\User;

class PublicShopService
{
    public function __construct(private readonly UserPresenceService $presence) {}

    public function ensureIsPublic(User $shop): void
    {
        abort_unless(
            $shop->role === UserRoleEnum::SHOP_OWNER && (bool) $shop->is_active,
            404,
        );
    }

    /** @return array{code: string, name: string, avatar_url: string|null, address: string, products_count: int} */
    public function featuredPayload(User $shop): array
    {
        return [
            'code' => (string) $shop->user_code,
            'name' => (string) ($shop->shop_name ?: 'Cửa hàng DealHay'),
            'avatar_url' => app(MediaClient::class)->url($shop->shop_avatar_url),
            'address' => collect([$shop->shop_ward, $shop->shop_province])
                ->filter()
                ->implode(', '),
            'products_count' => (int) $shop->getAttribute('products_count'),
        ];
    }

    /**
     * @param  array<string, mixed>  $engagementMetrics
     * @return array<string, mixed>
     */
    public function payload(User $shop, array $engagementMetrics): array
    {
        $lastActivity = $this->presence->lastSeenAt((string) $shop->getKey());

        return [
            'code' => (string) $shop->user_code,
            'name' => (string) ($shop->shop_name ?: 'Cửa hàng DealHay'),
            'email' => (string) ($shop->shop_email ?: ''),
            'phone_number' => (string) ($shop->shop_phone_number ?: ''),
            'avatar_url' => app(MediaClient::class)->url($shop->shop_avatar_url),
            'address' => collect([
                $shop->shop_address_detail,
                $shop->shop_ward,
                $shop->shop_province,
            ])->filter()->implode(', '),
            ...$engagementMetrics,
            'is_online' => $this->presence->isOnline((string) $shop->getKey()),
            'last_activity_at' => $lastActivity,
        ];
    }
}
