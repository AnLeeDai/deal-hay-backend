<?php

use App\Contracts\RewardedAdvertisementProvider;
use App\Models\ProductAdView;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\ProductDiscountCodeService;
use App\Services\RewardedAdvertisementService;
use Laravel\Sanctum\Sanctum;

class TestVerifiedAdvertisementProvider implements RewardedAdvertisementProvider
{
    public bool $verified = false;

    public ?string $fixedReference = null;

    public function start(ProductAdView $view): array
    {
        return ['session' => (string) $view->getKey()];
    }

    public function verifiedCompletionReference(ProductAdView $view): ?string
    {
        return $this->verified ? ($this->fixedReference ?? 'verified-event-'.$view->getKey()) : null;
    }
}

function systemAdvertisementProvider(array $campaign = []): TestVerifiedAdvertisementProvider
{
    config()->set('advertising.providers.test_verified', ['verified_rewards' => true]);
    config()->set('advertising.campaigns', ['system-credit' => [
        'enabled' => true, 'provider' => 'test_verified', 'ad_type' => 'rewarded_interstitial',
        'reward_type' => 'fixed', 'reward_value' => 25000, ...$campaign,
    ]]);
    $provider = new TestVerifiedAdvertisementProvider;
    app()->instance(RewardedAdvertisementService::class, new RewardedAdvertisementService(app(ProductDiscountCodeService::class), ['test_verified' => $provider]));

    return $provider;
}

function systemAdvertisementUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/advertisement';
}

it('returns 422 without creating a session or code when no verified provider is installed', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['old_price' => null]);
    Sanctum::actingAs($buyer);
    config()->set('advertising.campaigns', ['unverified-smartlink' => [
        'enabled' => true, 'provider' => 'adsterra_smartlink', 'reward_type' => 'fixed', 'reward_value' => 999999,
    ]]);

    $this->postJson(systemAdvertisementUrl($product), ['reward_value' => 4294967295, 'verified' => true])
        ->assertUnprocessable()->assertJsonValidationErrors('advertisement');

    $this->assertDatabaseCount('product_ad_views', 0);
    $this->assertDatabaseCount('product_discount_codes', 0);
    expect($product->hasAdvertisement())->toBeFalse();
});

it('selects a system campaign independently of seller prices and snapshots only server reward terms', function (): void {
    $provider = systemAdvertisementProvider();
    $product = ShopProduct::factory()->create(['old_price' => null, 'price' => 80000]);
    Sanctum::actingAs(User::factory()->customer()->create());

    $response = $this->postJson(systemAdvertisementUrl($product), [
        'provider' => 'attacker', 'campaign_id' => 'attacker', 'reward_type' => 'percentage', 'reward_value' => 10000,
    ])->assertCreated()->assertJsonPath('provider', 'test_verified')->assertJsonPath('ad_type', 'rewarded_interstitial');
    $view = ProductAdView::query()->findOrFail($response->json('id'));
    expect($view->provider)->toBe('test_verified')->and($view->campaign_id)->toBe('system-credit')
        ->and($view->reward_type)->toBe('fixed')->and($view->reward_value)->toBe(25000);
    expect($product->hasAdvertisement())->toBeTrue();
    $product->update(['price' => 200000, 'old_price' => 400000]);
    config()->set('advertising.campaigns.system-credit.reward_value', 999999);
    $provider->verified = true;

    $this->postJson(systemAdvertisementUrl($product).'/'.$view->getKey().'/complete', ['reward_value' => 4294967295])
        ->assertOk()->assertJsonPath('discount_code.reward_type', 'fixed')
        ->assertJsonPath('discount_code.reward_value', 25000)->assertJsonPath('discount_code.fixed_amount', 25000);

    $this->assertDatabaseHas('product_discount_codes', ['product_ad_view_id' => $view->getKey(), 'reward_type' => 'fixed', 'reward_value' => 25000]);
});

it('does not accept elapsed time or client claims as provider verification', function (): void {
    $this->freezeTime();
    $provider = systemAdvertisementProvider();
    $product = ShopProduct::factory()->create(['old_price' => null]);
    Sanctum::actingAs(User::factory()->customer()->create());
    $viewId = $this->postJson(systemAdvertisementUrl($product))->assertCreated()->json('id');
    $this->travel(30)->minutes();

    $this->postJson(systemAdvertisementUrl($product).'/'.$viewId.'/complete', [
        'verified_at' => now()->toISOString(), 'completed_at' => now()->toISOString(), 'verification_reference' => 'forged',
    ])->assertUnprocessable()->assertJsonValidationErrors('advertisement_view_id');
    $this->assertDatabaseCount('product_discount_codes', 0);
    expect(ProductAdView::query()->findOrFail($viewId)->verified_at)->toBeNull();
    $provider->verified = true;
    $first = $this->postJson(systemAdvertisementUrl($product).'/'.$viewId.'/complete')->assertOk();
    $this->postJson(systemAdvertisementUrl($product).'/'.$viewId.'/complete')->assertOk()
        ->assertJsonPath('discount_code.id', $first->json('discount_code.id'));

    $this->assertDatabaseCount('product_discount_codes', 1);
});

it('issues the snapshotted system percentage instead of the sellers advertised discount', function (): void {
    $provider = systemAdvertisementProvider(['ad_type' => 'rewarded_video', 'reward_type' => 'percentage', 'reward_value' => 1250]);
    $provider->verified = true;
    $product = ShopProduct::factory()->create(['price' => 1000, 'old_price' => 100000]);
    Sanctum::actingAs(User::factory()->customer()->create());
    $viewId = $this->postJson(systemAdvertisementUrl($product))->assertCreated()->json('id');

    $this->postJson(systemAdvertisementUrl($product).'/'.$viewId.'/complete')->assertOk()
        ->assertJsonPath('discount_code.reward_type', 'percentage')->assertJsonPath('discount_code.reward_value', 1250)
        ->assertJsonPath('discount_code.discount_percent', 12.5)->assertJsonPath('discount_code.original_price', 10000)
        ->assertJsonPath('discount_code.sale_price', 8750);

    $this->assertDatabaseCount('product_discount_codes', 1);
});

it('rejects a verified session completed by another owner or under another product', function (): void {
    $provider = systemAdvertisementProvider();
    $provider->verified = true;
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create();
    $otherProduct = ShopProduct::factory()->create(['shop_owner_id' => $product->shop_owner_id]);
    Sanctum::actingAs($buyer);
    $viewId = $this->postJson(systemAdvertisementUrl($product))->assertCreated()->json('id');
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->postJson(systemAdvertisementUrl($product).'/'.$viewId.'/complete')->assertNotFound();
    Sanctum::actingAs($buyer);
    $this->postJson(systemAdvertisementUrl($otherProduct).'/'.$viewId.'/complete')->assertNotFound();

    $this->assertDatabaseCount('product_discount_codes', 0);
});

it('returns 422 instead of issuing a second reward for a reused provider completion reference', function (): void {
    $provider = systemAdvertisementProvider();
    $provider->verified = true;
    $provider->fixedReference = 'one-provider-event';
    $product = ShopProduct::factory()->create(['old_price' => null]);
    Sanctum::actingAs(User::factory()->customer()->create());
    $first = $this->postJson(systemAdvertisementUrl($product))->assertCreated()->json('id');
    $second = $this->postJson(systemAdvertisementUrl($product))->assertCreated()->json('id');
    $this->postJson(systemAdvertisementUrl($product).'/'.$first.'/complete')->assertOk();

    $this->postJson(systemAdvertisementUrl($product).'/'.$second.'/complete')->assertUnprocessable()
        ->assertJsonValidationErrors('advertisement_view_id');

    $this->assertDatabaseCount('product_discount_codes', 1);
    expect(ProductAdView::query()->findOrFail($second)->verified_at)->toBeNull();
});
