<?php

namespace App\Models;

//  use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRoleEnum;
use App\Models\Traits\HasRemoteMedia;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['role', 'user_code', 'full_name', 'shop_name', 'email', 'shop_email', 'phone_number', 'shop_phone_number', 'shop_avatar_url', 'shop_province', 'shop_ward', 'shop_address_detail', 'province', 'ward', 'address_detail', 'avatar_url', 'password', 'is_active', 'lock_reason'])]
#[Hidden(['password', 'remember_token', 'session_version'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRemoteMedia, HasUuids, Notifiable;

    protected array $mediaAttributes = ['avatar_url', 'shop_avatar_url'];

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->shouldBlockAdministratorWrite()) {
                throw new \DomainException('Không được phép tạo thêm tài khoản quản trị viên.');
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'session_version' => 'integer',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    private function shouldBlockAdministratorWrite(): bool
    {
        if ($this->role !== UserRoleEnum::ADMINISTRATOR) {
            return false;
        }

        return ! $this->isConfiguredAdministrator();
    }

    private function isConfiguredAdministrator(): bool
    {
        return $this->email === config('AdminAccountConfig.ADMIN_EMAIL')
            && $this->user_code === config('AdminAccountConfig.ADMIN_USER_CODE');
    }

    public function generateUserCode(string $role): string
    {
        $prefix = match ($role) {
            UserRoleEnum::ADMINISTRATOR => 'AD',
            UserRoleEnum::SHOP_OWNER => 'SHPOW',
            UserRoleEnum::CUSTOMER => 'CUS',
            default => throw new \InvalidArgumentException('Loại tài khoản không hợp lệ.'),
        };

        $nextNumber = DB::transaction(function () use ($role) {
            $counter = DB::table('user_code_counters')
                ->where('role', $role)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                DB::table('user_code_counters')->insert([
                    'role' => $role,
                    'last_number' => 1,
                ]);

                return 1;
            }

            $nextNumber = $counter->last_number + 1;

            DB::table('user_code_counters')
                ->where('role', $role)
                ->update(['last_number' => $nextNumber]);

            return $nextNumber;
        });

        return sprintf('%s-%05d', $prefix, $nextNumber);
    }

    public function resolveDeviceName(string $deviceName): string
    {
        $deviceName = Str::of($deviceName)
            ->squish()
            ->limit(50)
            ->value();

        if ($deviceName === '') {
            return 'web-client';
        }

        return $deviceName;
    }

    public function getEmailForPasswordReset(): string
    {
        return hash('sha256', Str::lower((string) $this->email));
    }

    public function formatAddress(string $province, string $ward, string $addressDetail): string
    {
        return implode(', ', [$addressDetail, $ward, $province]);
    }

    public function shopProducts(): HasMany
    {
        return $this->hasMany(ShopProduct::class, 'shop_owner_id');
    }

    public function shopCoupons(): HasMany
    {
        return $this->hasMany(ShopCoupon::class, 'shop_owner_id');
    }

    public function shopOrders(): HasMany
    {
        return $this->hasMany(ShopOrder::class, 'shop_owner_id');
    }

    public function shopInventoryMovements(): HasMany
    {
        return $this->hasMany(ShopInventoryMovement::class, 'shop_owner_id');
    }

    public function productCategories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'product_category_shop', 'shop_owner_id', 'product_category_id')
            ->withPivot('display_order');
    }

    public function shopProfileChangeLogs(): HasMany
    {
        return $this->hasMany(ShopProfileChangeLog::class, 'shop_owner_id');
    }

    public function shopFollowers(): HasMany
    {
        return $this->hasMany(ShopFollow::class, 'shop_owner_id');
    }

    public function followedShops(): HasMany
    {
        return $this->hasMany(ShopFollow::class, 'follower_id');
    }

    public function shopReactions(): HasMany
    {
        return $this->hasMany(ShopReaction::class, 'shop_owner_id');
    }

    public function givenShopReactions(): HasMany
    {
        return $this->hasMany(ShopReaction::class, 'customer_id');
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ShopProductReview::class, 'customer_id');
    }

    public function buyerChatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'buyer_id');
    }

    public function shopChatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'shop_owner_id');
    }
}
