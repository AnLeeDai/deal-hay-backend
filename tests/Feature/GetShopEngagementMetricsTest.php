<?php

use App\Actions\GetShopEngagementMetrics;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ShopFollow;
use App\Models\ShopProduct;
use App\Models\ShopReaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('returns zero engagement counts and null rates for an empty shop', function (bool $authenticated): void {
    $shop = User::factory()->shopOwner()->create();
    $viewer = $authenticated ? User::factory()->customer()->create() : null;

    $metrics = app(GetShopEngagementMetrics::class)->handle($shop, $viewer);

    expect($metrics)->toBe([
        'products_count' => 0,
        'total_products_count' => 0,
        'hidden_products_count' => 0,
        'followers_count' => 0,
        'likes_count' => 0,
        'dislikes_count' => 0,
        'reactions_count' => 0,
        'satisfaction_rate' => null,
        'response_rate' => null,
        'customer_conversations_count' => 0,
        'responded_conversations_count' => 0,
        'viewer' => ['can_interact' => $authenticated, 'is_following' => false, 'reaction' => null],
    ]);
})->with(['anonymous' => false, 'customer' => true]);

it('counts shop engagement in four queries and reflects viewer changes immediately', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $viewer = User::factory()->customer()->create();
    ShopProduct::factory()->count(2)->for($shop, 'shopOwner')->create();
    ShopProduct::factory()->for($shop, 'shopOwner')->create(['is_active' => false]);
    $reaction = ShopReaction::factory()->for($shop, 'shopOwner')->for($viewer, 'customer')->create(['reaction' => 'like']);
    ShopReaction::factory()->for($shop, 'shopOwner')->create(['reaction' => 'like']);
    ShopReaction::factory()->for($shop, 'shopOwner')->create(['reaction' => 'dislike']);
    $follow = ShopFollow::factory()->for($shop, 'shopOwner')->for($viewer, 'follower')->create();
    ShopFollow::factory()->for($shop, 'shopOwner')->create();
    $buyerOnly = ChatConversation::factory()->for($shop, 'shopOwner')->create();
    ChatMessage::factory()->count(2)->for($buyerOnly, 'conversation')->create(['sender_id' => $buyerOnly->buyer_id]);
    $responded = ChatConversation::factory()->for($shop, 'shopOwner')->create();
    ChatMessage::factory()->for($responded, 'conversation')->create(['sender_id' => $responded->buyer_id]);
    ChatMessage::factory()->count(2)->for($responded, 'conversation')->create(['sender_id' => $shop->id]);
    $shopOnly = ChatConversation::factory()->for($shop, 'shopOwner')->create();
    ChatMessage::factory()->for($shopOnly, 'conversation')->create(['sender_id' => $shop->id]);
    ChatConversation::factory()->for($shop, 'shopOwner')->create();
    ShopProduct::factory()->create();
    ShopReaction::factory()->create(['reaction' => 'like']);
    ShopFollow::factory()->create();
    DB::enableQueryLog();

    try {
        $metrics = app(GetShopEngagementMetrics::class)->handle($shop, $viewer);
        $queryCount = count(DB::getQueryLog());
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($queryCount)->toBe(4);
    expect($metrics)->toBe([
        'products_count' => 2,
        'total_products_count' => 3,
        'hidden_products_count' => 1,
        'followers_count' => 2,
        'likes_count' => 2,
        'dislikes_count' => 1,
        'reactions_count' => 3,
        'satisfaction_rate' => 67,
        'response_rate' => 50,
        'customer_conversations_count' => 2,
        'responded_conversations_count' => 1,
        'viewer' => ['can_interact' => true, 'is_following' => true, 'reaction' => 'like'],
    ]);

    $reaction->update(['reaction' => 'dislike']);
    $follow->delete();
    $updated = app(GetShopEngagementMetrics::class)->handle($shop, $viewer);

    expect($updated['likes_count'])->toBe(1);
    expect($updated['dislikes_count'])->toBe(2);
    expect($updated['followers_count'])->toBe(1);
    expect($updated['viewer'])->toBe(['can_interact' => true, 'is_following' => false, 'reaction' => 'dislike']);
});
