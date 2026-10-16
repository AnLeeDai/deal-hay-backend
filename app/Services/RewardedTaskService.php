<?php

namespace App\Services;

use App\Enums\UserRoleEnum;
use App\Models\ProductDiscountCode;
use App\Models\RewardedTaskConversion;
use App\Models\RewardedTaskSession;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardedTaskService
{
    public function __construct(private readonly ProductDiscountCodeService $codes) {}

    public function isEnabled(): bool
    {
        return config('rewarded_tasks.provider') === 'adgem'
            && (bool) config('rewarded_tasks.adgem.enabled')
            && (bool) config('rewarded_tasks.adgem.reward_mapping_confirmed')
            && preg_match('/^[1-9][0-9]*$/', (string) config('rewarded_tasks.adgem.app_id')) === 1
            && trim((string) config('rewarded_tasks.adgem.postback_key')) !== ''
            && (int) config('rewarded_tasks.reward_amount') > 0
            && (int) config('rewarded_tasks.reward_amount') <= 1_000_000;
    }

    public function ensureProductIsPublic(User $shop, ShopProduct $product): void
    {
        abort_unless($product->shop_owner_id === $shop->getKey()
            && (bool) $product->is_active && (bool) $shop->is_active
            && $shop->role === UserRoleEnum::SHOP_OWNER, 404);
    }

    /** @return array{enabled: bool, provider: string, reward_amount: int, reason?: string} */
    public function configuration(): array
    {
        $enabled = $this->isEnabled();
        $configuration = [
            'enabled' => $enabled,
            'provider' => (string) config('rewarded_tasks.provider'),
            'reward_amount' => (int) config('rewarded_tasks.reward_amount', 1000),
        ];

        if (! $enabled) {
            $configuration['reason'] = 'Nhiệm vụ nhận mã giảm giá chưa được kích hoạt. Vui lòng quay lại sau.';
        }

        return $configuration;
    }

    public function start(User $user, ShopProduct $product): RewardedTaskSession
    {
        abort_unless($this->isEnabled(), 503, 'Nhiệm vụ nhận mã giảm giá chưa được kích hoạt.');

        return RewardedTaskSession::query()->create([
            'user_id' => $user->getKey(),
            'shop_product_id' => $product->getKey(),
            'provider' => 'adgem',
            'app_id' => (string) config('rewarded_tasks.adgem.app_id'),
            'player_id' => hash('sha256', 'deal-hay:adgem:user:'.$user->getKey()),
            'binding_token' => bin2hex(random_bytes(32)),
            'reward_amount' => (int) config('rewarded_tasks.reward_amount'),
            'expires_at' => now()->addDays((int) config('rewarded_tasks.session_days', 90)),
        ]);
    }

    /** @return array<string, mixed> */
    public function payload(RewardedTaskSession $session): array
    {
        $codes = $session->conversions()->with('discountCode.product.shopOwner')->oldest()->get()
            ->map(fn (RewardedTaskConversion $conversion): ?ProductDiscountCode => $conversion->discountCode)
            ->filter()->values();

        return [
            'id' => (string) $session->getKey(),
            'status' => ! $session->expires_at->isFuture() ? 'expired' : ($codes->isNotEmpty() ? 'rewarded' : 'pending'),
            'reward_amount' => $session->reward_amount,
            'expires_at' => $session->expires_at->toISOString(),
            'offerwall_url' => 'https://api.adgem.com/v1/wall?'.http_build_query([
                'appid' => $session->app_id,
                'playerid' => $session->player_id,
                'c1' => $session->getKey(),
                'c2' => $session->binding_token,
            ], '', '&', PHP_QUERY_RFC3986),
            'discount_codes' => $codes->map(fn (ProductDiscountCode $code): array => $this->codes->payload($code))->all(),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function receiveAdGemConversion(array $payload): string
    {
        $data = $payload['data'];
        abort_unless(hash_equals((string) config('rewarded_tasks.adgem.app_id'), $data['app_id']), 403);

        return DB::transaction(function () use ($payload, $data): string {
            $session = RewardedTaskSession::query()->lockForUpdate()->findOrFail($data['c1']);
            abort_unless($session->provider === 'adgem'
                && hash_equals($session->app_id, $data['app_id'])
                && hash_equals($session->player_id, $data['player_id'])
                && hash_equals($session->binding_token, $data['c2']), 403);

            if ($data['conversion_type'] !== 'reward' || round((float) $data['payout'], 8) <= 0 || round((float) $data['amount'], 8) <= 0) {
                return 'ignored';
            }

            $conversionKey = hash('sha256', json_encode([
                'adgem', $data['app_id'], $data['conversion_id'], $data['goal_id'] ?? '',
            ], JSON_THROW_ON_ERROR));
            $conversionId = (string) Str::uuid();
            $inserted = RewardedTaskConversion::query()->insertOrIgnore([
                'id' => $conversionId,
                'rewarded_task_session_id' => $session->getKey(),
                'provider' => 'adgem',
                'app_id' => $data['app_id'],
                'conversion_key' => $conversionKey,
                'request_id' => $payload['request_id'],
                'conversion_id' => $data['conversion_id'],
                'goal_id' => $data['goal_id'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'offer_id' => $data['offer_id'] ?? null,
                'payout' => $data['payout'],
                'provider_amount' => $data['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                $existing = RewardedTaskConversion::query()->where('conversion_key', $conversionKey)->lockForUpdate()->first();
                abort_unless($existing !== null && $existing->rewarded_task_session_id === $session->getKey(), 409);

                return 'duplicate';
            }

            $product = $session->product;
            $originalPrice = max(1, (int) $product->price);
            $code = ProductDiscountCode::query()->create([
                'user_id' => $session->user_id,
                'shop_product_id' => $session->shop_product_id,
                'code' => 'DH'.Str::upper(bin2hex(random_bytes(15))),
                'original_price' => $originalPrice,
                'sale_price' => max(0, $originalPrice - $session->reward_amount),
                'reward_type' => 'fixed',
                'reward_value' => $session->reward_amount,
                'saved_at' => now(),
                'expires_at' => now()->addDays((int) config('rewarded_tasks.coupon_days', 30)),
            ]);
            RewardedTaskConversion::query()->whereKey($conversionId)->update(['product_discount_code_id' => $code->getKey()]);

            return 'rewarded';
        }, attempts: 5);
    }
}
