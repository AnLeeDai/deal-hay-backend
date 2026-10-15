<?php

use App\Models\ProductAdView;
use App\Models\ShopProduct;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function retiredAdvertisementUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/advertisement';
}

it('returns 404 for retired rewarded session creation without changing saved rewards', function (string $role): void {
    $buyer = $role === 'administrator'
        ? User::query()->where('role', 'administrator')->firstOrFail()
        : User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['old_price' => null]);
    Sanctum::actingAs($buyer);

    $this->postJson(retiredAdvertisementUrl($product), ['reward_type' => 'fixed', 'reward_value' => 4294967295])
        ->assertNotFound();

    $this->assertDatabaseCount('product_ad_views', 0);
    $this->assertDatabaseCount('product_discount_codes', 0);
})->with(['customer', 'administrator']);

it('cannot restore the retired endpoint through old provider configuration or client claims', function (): void {
    $product = ShopProduct::factory()->create();
    Sanctum::actingAs(User::factory()->customer()->create());
    config()->set('advertising.providers.adsterra_smartlink.verified_rewards', true);
    config()->set('advertising.campaigns.old_campaign', [
        'enabled' => true, 'provider' => 'adsterra_smartlink', 'reward_type' => 'fixed', 'reward_value' => 999999,
    ]);

    $this->postJson(retiredAdvertisementUrl($product), ['provider' => 'adsterra_smartlink', 'verified' => true])
        ->assertNotFound();

    $this->assertDatabaseCount('product_ad_views', 0);
    $this->assertDatabaseCount('product_discount_codes', 0);
});

it('never mints rewards when old clients complete a historical session after elapsed time', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create();
    $view = ProductAdView::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'provider' => 'historical_provider', 'campaign_id' => 'historical_campaign',
        'reward_type' => 'fixed', 'reward_value' => 50000,
        'video_url' => '', 'duration_seconds' => 0, 'expires_at' => now()->addHour(),
    ]);
    Sanctum::actingAs($buyer);
    $this->travel(30)->minutes();

    $this->postJson(retiredAdvertisementUrl($product).'/'.$view->getKey().'/complete', [
        'verified_at' => now()->toISOString(), 'completed_at' => now()->toISOString(),
        'verification_reference' => 'client-claim',
    ])->assertNotFound();

    $this->assertDatabaseCount('product_discount_codes', 0);
    expect($view->fresh()->completed_at)->toBeNull()->and($view->fresh()->verified_at)->toBeNull();
});

it('omits retired rewarded advertising capabilities from public products', function (): void {
    $product = ShopProduct::factory()->create([
        'price' => 80000, 'old_price' => 100000,
        'advertisement_video_url' => 'https://example.com/historical-shop-ad.mp4',
        'advertisement_duration_seconds' => 15,
    ]);

    $this->getJson('/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug)
        ->assertOk()->assertJsonMissingPath('product.advertisement_available')
        ->assertJsonMissingPath('product.advertisement_video_url')
        ->assertJsonMissingPath('product.advertisement_duration_seconds');
});
