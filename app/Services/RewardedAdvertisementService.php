<?php

namespace App\Services;

use App\Contracts\RewardedAdvertisementProvider;
use App\Models\ProductAdView;
use App\Models\ProductDiscountCode;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardedAdvertisementService
{
    /** @param array<string, RewardedAdvertisementProvider> $providers */
    public function __construct(private readonly ProductDiscountCodeService $codes, private readonly array $providers = []) {}

    /** @return array<string, mixed>|null */
    public function availableCampaign(): ?array
    {
        foreach ((array) config('advertising.campaigns', []) as $id => $campaign) {
            $provider = $campaign['provider'] ?? '';
            $type = $campaign['reward_type'] ?? '';
            $value = $campaign['reward_value'] ?? 0;
            if (($campaign['enabled'] ?? false) && isset($this->providers[$provider])
                && config('advertising.providers.'.$provider.'.verified_rewards') === true
                && in_array($type, ['fixed', 'percentage'], true) && is_int($value) && $value > 0
                && $value <= ($type === 'percentage' ? 10000 : 4294967295)) {
                return [...$campaign, 'id' => (string) $id];
            }
        }

        return null;
    }

    /** @return array{view: ProductAdView, presentation: array<string, mixed>} */
    public function start(User $user, ShopProduct $product): array
    {
        $campaign = $this->availableCampaign();
        if ($campaign === null) {
            throw ValidationException::withMessages(['advertisement' => 'Quảng cáo nhận thưởng hiện chưa khả dụng. Vui lòng thử lại sau.']);
        }

        return DB::transaction(function () use ($user, $product, $campaign): array {
            $view = ProductAdView::query()->create([
                'user_id' => $user->getKey(), 'shop_product_id' => $product->getKey(),
                'provider' => $campaign['provider'], 'campaign_id' => $campaign['id'],
                'ad_type' => $campaign['ad_type'] ?? 'rewarded', 'reward_type' => $campaign['reward_type'],
                'reward_value' => $campaign['reward_value'], 'video_url' => '', 'duration_seconds' => 0,
                'expires_at' => now()->addHour(),
            ]);
            $presentation = $this->providers[$view->provider]->start($view);
            $view->update(['provider_session' => $presentation]);

            return ['view' => $view, 'presentation' => $presentation];
        }, attempts: 5);
    }

    public function complete(User $user, ProductAdView $view): ProductDiscountCode
    {
        abort_unless($view->user_id === $user->getKey(), 404);
        if (! $view->provider || ! isset($this->providers[$view->provider])
            || config('advertising.providers.'.$view->provider.'.verified_rewards') !== true
            || ! $view->campaign_id || ! $view->expires_at->isFuture() || $view->shop_order_id !== null) {
            throw ValidationException::withMessages(['advertisement_view_id' => 'Phiên quảng cáo không có xác nhận nhận thưởng hợp lệ từ nhà cung cấp.']);
        }
        $reference = $this->providers[$view->provider]->verifiedCompletionReference($view);
        if (! is_string($reference) || $reference === '') {
            throw ValidationException::withMessages(['advertisement_view_id' => 'Nhà cung cấp chưa xác nhận hoàn tất quảng cáo.']);
        }
        try {
            return DB::transaction(function () use ($user, $view, $reference): ProductDiscountCode {
                if (ProductAdView::query()->where('provider', $view->provider)->where('verification_reference', $reference)
                    ->whereKeyNot($view->getKey())->exists()) {
                    throw ValidationException::withMessages(['advertisement_view_id' => 'Lượt hoàn tất quảng cáo này đã được ghi nhận.']);
                }
                $view->update(['completed_at' => $view->completed_at ?? now(), 'verified_at' => $view->verified_at ?? now(), 'verification_reference' => $reference]);

                return $this->codes->issueFromCompletedView($user, $view);
            }, attempts: 5);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['advertisement_view_id' => 'Lượt hoàn tất quảng cáo này đã được ghi nhận.']);
        }
    }
}
