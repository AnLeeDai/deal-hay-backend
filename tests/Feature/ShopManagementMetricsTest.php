<?php

use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

it('returns zero order metrics for a shop with no orders', function (): void {
    $shop = User::factory()->shopOwner()->create();
    Sanctum::actingAs($shop);

    $this->getJson('/api/private/shop/manage')
        ->assertOk()
        ->assertJsonPath('shopMetrics.total_orders_count', 0)
        ->assertJsonPath('shopMetrics.completed_orders_count', 0)
        ->assertJsonPath('shopMetrics.open_orders_count', 0)
        ->assertJsonPath('shopMetrics.total_revenue', 0);
});

it('aggregates order metrics in one query and excludes other shops and unfinished revenue', function (): void {
    $shop = User::factory()->shopOwner()->create();
    ShopOrder::factory()->for($shop, 'shopOwner')->count(7)->sequence(
        ['status' => 'pending', 'total' => 900_000],
        ['status' => 'confirmed', 'total' => 900_000],
        ['status' => 'processing', 'total' => 900_000],
        ['status' => 'shipping', 'total' => 900_000],
        ['status' => 'completed', 'total' => 100_000],
        ['status' => 'completed', 'total' => 200_000],
        ['status' => 'cancelled', 'total' => 900_000],
    )->create();
    ShopOrder::factory()->create(['status' => 'completed', 'total' => 9_000_000]);
    Sanctum::actingAs($shop);

    DB::enableQueryLog();
    try {
        $response = $this->getJson('/api/private/shop/manage');
        $orderQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'from "shop_orders"'));
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    $response->assertOk()
        ->assertJsonPath('shopMetrics.total_orders_count', 7)
        ->assertJsonPath('shopMetrics.completed_orders_count', 2)
        ->assertJsonPath('shopMetrics.open_orders_count', 4)
        ->assertJsonPath('shopMetrics.total_revenue', 300_000);
    expect($orderQueries)->toHaveCount(2);
});
