<?php

use App\Actions\GetShopProductReviews;
use App\Models\ShopProduct;
use App\Models\ShopProductReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('summarizes every review in one aggregate query even when the public list is limited', function (): void {
    $product = ShopProduct::factory()->create();
    ShopProductReview::factory()->count(50)->for($product, 'product')->create(['reaction' => 'like']);
    ShopProductReview::factory()->for($product, 'product')->create(['reaction' => 'dislike']);
    ShopProductReview::factory()->create(['reaction' => 'dislike']);
    DB::enableQueryLog();

    try {
        $response = $this->getJson('/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug);
        $reviewQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'shop_product_reviews'));
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    $response->assertOk()
        ->assertJsonCount(50, 'reviews')
        ->assertJsonPath('reviewSummary', ['total_count' => 51, 'likes_count' => 50, 'dislikes_count' => 1]);
    expect($reviewQueries)->toHaveCount(2);
});

it('reuses a listed viewer review and checks review permissions once', function (): void {
    $product = ShopProduct::factory()->create();
    $viewer = User::factory()->customer()->create();
    $review = ShopProductReview::factory()->for($product, 'product')->for($viewer, 'customer')->create(['reaction' => 'like']);
    DB::enableQueryLog();

    try {
        $data = app(GetShopProductReviews::class)->productPage($product, $viewer);
        $queryCount = count(DB::getQueryLog());
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($queryCount)->toBe(5);
    expect($data['viewer_review']['id'])->toBe($review->id);
    expect($data['viewer_review'])->toBe($data['reviews'][0]);
    expect($data['can_review'])->toBeFalse();
});

it('still returns a viewer review older than the public list', function (): void {
    $product = ShopProduct::factory()->create();
    $viewer = User::factory()->customer()->create();
    $review = ShopProductReview::factory()->for($product, 'product')->for($viewer, 'customer')->create([
        'created_at' => '2026-01-01 00:00:00',
    ]);
    ShopProductReview::factory()->count(50)->for($product, 'product')->create(['created_at' => '2026-01-02 00:00:00']);

    $data = app(GetShopProductReviews::class)->productPage($product, $viewer);

    expect($data['reviews'])->toHaveCount(50);
    expect(array_column($data['reviews'], 'id'))->not->toContain($review->id);
    expect($data['viewer_review']['id'])->toBe($review->id);
});
