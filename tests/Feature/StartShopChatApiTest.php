<?php

use App\Events\ChatMessageSentEvent;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

it('starts product chats with the selected variant and its effective price', function (?int $variantPrice, int $effectivePrice): void {
    $shop = User::factory()->shopOwner()->create();
    $customer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 50_000]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create([
        'sku' => 'SHIRT-WHITE-M',
        'option_values_snapshot' => ['Màu sắc' => 'Trắng', 'Kích thước' => 'M'],
        'price' => $variantPrice,
        'stock' => 3,
    ]);
    Queue::fake([BroadcastEvent::class]);

    $response = $this->actingAs($customer, 'sanctum')->postJson('/api/shops/'.$shop->user_code.'/chat', [
        'product' => $product->id,
        'variant' => $variant->id,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('chat_messages', [
        'chat_conversation_id' => $response->json('conversation.id'),
        'sender_id' => $customer->id,
        'shop_product_id' => $product->id,
        'shop_product_variant_id' => $variant->id,
    ]);
    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($variant, $effectivePrice): bool {
        if (! $job->event instanceof ChatMessageSentEvent) {
            return false;
        }

        $productPayload = $job->event->broadcastWith()['product'];

        return $productPayload['variant_id'] === $variant->id
            && $productPayload['sku'] === 'SHIRT-WHITE-M'
            && $productPayload['option_values'] === ['Màu sắc' => 'Trắng', 'Kích thước' => 'M']
            && $productPayload['price'] === $effectivePrice
            && $productPayload['is_available'] === true;
    });
})->with([
    'variant price' => [60_000, 60_000],
    'inherited product price' => [null, 50_000],
]);

it('returns 422 when the selected chat variant is inactive', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $customer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $variant = ShopProductVariant::factory()->for($product, 'product')->inactive()->create();

    $this->actingAs($customer, 'sanctum')->postJson('/api/shops/'.$shop->user_code.'/chat', [
        'product' => $product->id,
        'variant' => $variant->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('variant');

    $this->assertDatabaseCount('chat_messages', 0);
    $this->assertDatabaseCount('chat_conversations', 0);
});

it('returns 422 when the selected chat variant belongs to a different product', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $customer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $otherProduct = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $variant = ShopProductVariant::factory()->for($otherProduct, 'product')->create();

    $this->actingAs($customer, 'sanctum')->postJson('/api/shops/'.$shop->user_code.'/chat', [
        'product' => $product->id,
        'variant' => $variant->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('variant');

    $this->assertDatabaseCount('chat_messages', 0);
    $this->assertDatabaseCount('chat_conversations', 0);
});
