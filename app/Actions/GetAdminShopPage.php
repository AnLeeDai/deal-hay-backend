<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\ShopProfileChangeLog;
use App\Models\User;
use App\Services\UserPresenceService;
use App\Support\AdminShopProductPayload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class GetAdminShopPage
{
    public function __construct(private readonly UserPresenceService $presence) {}

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters, ?User $selectedShop = null): array
    {
        if ($selectedShop !== null && $selectedShop->role !== UserRoleEnum::SHOP_OWNER) {
            abort(404);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $status = in_array($status, ['active', 'locked'], true) ? $status : 'all';
        $activeCategoryCount = ProductCategory::query()->where('is_active', true)->count();

        /** @var LengthAwarePaginator<int, User> $shops */
        $shops = User::query()
            ->where('role', UserRoleEnum::SHOP_OWNER)
            ->select([
                'id',
                'role',
                'user_code',
                'full_name',
                'shop_name',
                'email',
                'shop_email',
                'phone_number',
                'shop_phone_number',
                'avatar_url',
                'shop_avatar_url',
                'province',
                'ward',
                'address_detail',
                'shop_province',
                'shop_ward',
                'shop_address_detail',
                'is_active',
                'lock_reason',
                'created_at',
                'updated_at',
            ])
            ->withCount(['shopProducts', 'shopProfileChangeLogs'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('shop_name', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('shop_email', 'like', "%{$search}%")
                        ->orWhere('shop_phone_number', 'like', "%{$search}%")
                        ->orWhere('user_code', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'locked', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $shop): array => $this->shopPayload($shop, $activeCategoryCount));

        $selectedShop?->loadMissing([
            'shopProducts' => fn ($query) => $query->with('productCategory:id,name')->latest(),
            'shopProfileChangeLogs' => fn ($query) => $query->latest(),
        ]);
        $selectedShop?->loadCount(['shopProducts', 'shopProfileChangeLogs']);

        return [
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'selectedShop' => $selectedShop
                ? $this->shopPayload($selectedShop, $activeCategoryCount, true)
                : null,
            'shops' => $shops,
        ];
    }

    /** @return array<string, mixed> */
    private function shopPayload(User $shop, int $activeCategoryCount, bool $includeDetails = false): array
    {
        $serializedShop = $shop->toArray();
        $lastActivity = $this->presence->lastSeenAt((string) $shop->getKey());

        $payload = [
            'id' => $shop->id,
            'role' => $shop->role,
            'user_code' => $shop->user_code,
            'full_name' => $shop->full_name,
            'shop_name' => $shop->shop_name,
            'email' => $shop->email,
            'shop_email' => $shop->shop_email,
            'phone_number' => $shop->phone_number,
            'shop_phone_number' => $shop->shop_phone_number,
            'avatar_url' => $serializedShop['avatar_url'] ?? null,
            'shop_avatar_url' => $serializedShop['shop_avatar_url'] ?? null,
            'province' => $shop->province,
            'ward' => $shop->ward,
            'address_detail' => $shop->address_detail,
            'personal_address' => $this->address([
                $shop->address_detail,
                $shop->ward,
                $shop->province,
            ]),
            'shop_province' => $shop->shop_province,
            'shop_ward' => $shop->shop_ward,
            'shop_address_detail' => $shop->shop_address_detail,
            'shop_address' => $this->address([
                $shop->shop_address_detail,
                $shop->shop_ward,
                $shop->shop_province,
            ]),
            'is_active' => (bool) $shop->is_active,
            'lock_reason' => $shop->lock_reason,
            'products_count' => (int) ($shop->getAttribute('shop_products_count') ?? 0),
            'categories_count' => $activeCategoryCount,
            'change_logs_count' => (int) ($shop->getAttribute('shop_profile_change_logs_count') ?? 0),
            'online_status' => $this->onlineStatus(
                $this->presence->isOnline((string) $shop->getKey()),
                $lastActivity,
            ),
            'created_at' => $shop->created_at?->toISOString(),
            'updated_at' => $shop->updated_at?->toISOString(),
        ];

        if (! $includeDetails) {
            return $payload;
        }

        $assignedCategories = $shop->productCategories()
            ->where('is_active', true)
            ->orderByPivot('display_order')
            ->get();
        $categories = $assignedCategories->concat(
            ProductCategory::query()
                ->where('is_active', true)
                ->whereKeyNot($assignedCategories->modelKeys())
                ->orderBy('name')
                ->get(),
        );

        return [
            ...$payload,
            'categories' => $categories
                ->map(fn (ProductCategory $category, int $index): array => [
                    'id' => (string) $category->id,
                    'name' => (string) $category->name,
                    'display_order' => $index + 1,
                    'is_active' => (bool) $category->is_active,
                ])
                ->values()
                ->all(),
            'products' => $shop->shopProducts
                ->map(fn (ShopProduct $product): array => AdminShopProductPayload::from($product))
                ->values()
                ->all(),
            'change_logs' => $shop->shopProfileChangeLogs
                ->map(fn (ShopProfileChangeLog $log): array => [
                    'id' => (string) $log->id,
                    'changes' => $log->changes,
                    'created_at' => $log->created_at?->toISOString(),
                ])
                ->values()
                ->all(),
        ];
    }

    /** @param list<string|null> $parts */
    private function address(array $parts): string
    {
        $addressParts = collect($parts)
            ->filter(fn (?string $value): bool => filled($value))
            ->map(fn (string $value): string => trim($value))
            ->values();

        return $addressParts->isNotEmpty() ? $addressParts->join(', ') : 'Chưa cập nhật';
    }

    /** @return array{is_online: bool, label: string, last_activity_at: int|null} */
    private function onlineStatus(bool $isOnline, ?int $lastActivity): array
    {
        if (! $lastActivity) {
            return [
                'is_online' => false,
                'label' => 'Chưa hoạt động',
                'last_activity_at' => null,
            ];
        }

        return [
            'is_online' => $isOnline,
            'label' => $isOnline
                ? 'Đang online'
                : 'Hoạt động '.Carbon::createFromTimestamp($lastActivity)->diffForHumans(),
            'last_activity_at' => $lastActivity,
        ];
    }
}
