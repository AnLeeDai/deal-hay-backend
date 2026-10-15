<?php

use App\Actions\SaveShopProductReview;
use App\Enums\UserRoleEnum;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductReview;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

function reviewEligibilityOrder(User $customer, ShopProduct $product, array $attributes = []): ShopOrder
{
    $order = ShopOrder::factory()->create([
        'shop_owner_id' => $product->shop_owner_id,
        'customer_id' => $customer->getKey(),
        ...$attributes,
    ]);
    $order->items()->create([
        'shop_product_id' => $product->getKey(),
        'product_name' => $product->name,
        'sku' => $product->sku,
        'unit_price' => $product->price,
        'quantity' => 1,
        'subtotal' => $product->price,
    ]);

    return $order;
}

function reviewEligibilityUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug;
}

it('locks product reviews for visitors and customers who have not ordered', function (): void {
    $product = ShopProduct::factory()->create();
    $customer = User::factory()->customer()->create();

    $this->getJson(reviewEligibilityUrl($product))
        ->assertOk()
        ->assertJsonPath('canReview', false);

    $this->withToken($customer->createToken('review-nonbuyer')->plainTextToken)
        ->getJson(reviewEligibilityUrl($product))
        ->assertOk()
        ->assertJsonPath('canReview', false);

    $this->putJson(reviewEligibilityUrl($product).'/review', [
        'reaction' => 'like',
        'content' => 'Tôi chưa đặt sản phẩm này.',
    ])->assertForbidden();

    $this->assertDatabaseCount('shop_product_reviews', 0);
});

it('allows customers shop owners and administrators to review their purchases', function (string $role): void {
    $product = ShopProduct::factory()->create();
    $customer = $role === 'administrator'
        ? User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail()
        : User::factory()->{$role}()->create();
    reviewEligibilityOrder($customer, $product);

    $this->withToken($customer->createToken('review-buyer')->plainTextToken)
        ->getJson(reviewEligibilityUrl($product))
        ->assertOk()
        ->assertJsonPath('canReview', true);

    $this->putJson(reviewEligibilityUrl($product).'/review', [
        'reaction' => 'like',
        'content' => 'Sản phẩm tốt và đúng mô tả.',
    ])->assertOk();

    $this->assertDatabaseHas('shop_product_reviews', [
        'customer_id' => $customer->getKey(),
        'shop_product_id' => $product->getKey(),
        'content' => 'Sản phẩm tốt và đúng mô tả.',
    ]);
})->with(['customer', 'shopOwner', 'administrator']);

it('allows reviews after a noncancelled order has been placed', function (string $status): void {
    $product = ShopProduct::factory()->create();
    $customer = User::factory()->customer()->create();
    reviewEligibilityOrder($customer, $product, ['status' => $status]);

    $this->withToken($customer->createToken('review-order-status')->plainTextToken)
        ->putJson(reviewEligibilityUrl($product).'/review', [
            'reaction' => 'like',
            'content' => 'Đánh giá sau khi đặt hàng.',
        ])->assertOk();

    $this->assertDatabaseHas('shop_product_reviews', [
        'customer_id' => $customer->getKey(),
        'shop_product_id' => $product->getKey(),
    ]);
})->with(['pending', 'confirmed', 'processing', 'shipping', 'completed']);

it('rejects cancelled and unrecognized order statuses for review eligibility', function (string $status, bool $cancelled): void {
    $product = ShopProduct::factory()->create();
    $customer = User::factory()->customer()->create();
    reviewEligibilityOrder($customer, $product, [
        'status' => $status,
        'cancelled_at' => $cancelled ? now() : null,
    ]);

    $this->withToken($customer->createToken('review-invalid-order-status')->plainTextToken)
        ->getJson(reviewEligibilityUrl($product))
        ->assertOk()
        ->assertJsonPath('canReview', false);

    $this->putJson(reviewEligibilityUrl($product).'/review', [
        'reaction' => 'like',
        'content' => 'Đánh giá đơn không hợp lệ.',
    ])->assertForbidden();
})->with([
    'cancelled status' => ['cancelled', false],
    'unknown status' => ['draft', false],
    'cancelled timestamp' => ['pending', true],
]);

it('requires an order for the exact product belonging to the authenticated buyer', function (): void {
    $product = ShopProduct::factory()->create();
    $otherProduct = ShopProduct::factory()->create(['shop_owner_id' => $product->shop_owner_id]);
    $customer = User::factory()->customer()->create();
    $otherCustomer = User::factory()->customer()->create();
    reviewEligibilityOrder($customer, $otherProduct);
    reviewEligibilityOrder($otherCustomer, $product);

    $this->withToken($customer->createToken('review-wrong-purchase')->plainTextToken)
        ->putJson(reviewEligibilityUrl($product).'/review', [
            'reaction' => 'like',
            'content' => 'Không thể dùng đơn hàng khác.',
        ])->assertForbidden();

    $this->assertDatabaseCount('shop_product_reviews', 0);
});

it('does not infer buyer identity from editable order contact information', function (): void {
    $product = ShopProduct::factory()->create();
    $customer = User::factory()->customer()->create(['phone_number' => '0901234567']);
    reviewEligibilityOrder($customer, $product, [
        'customer_id' => null,
        'customer_email' => $customer->email,
        'customer_phone' => $customer->phone_number,
    ]);

    $this->withToken($customer->createToken('review-contact-spoof')->plainTextToken)
        ->putJson(reviewEligibilityUrl($product).'/review', [
            'reaction' => 'like',
            'content' => 'Thông tin liên hệ không chứng minh đã mua.',
        ])->assertForbidden();
});

it('prevents a shop owner from reviewing their own product even with an order', function (): void {
    $product = ShopProduct::factory()->create();
    $shop = $product->shopOwner;
    reviewEligibilityOrder($shop, $product);

    $this->withToken($shop->createToken('review-self-purchase')->plainTextToken)
        ->putJson(reviewEligibilityUrl($product).'/review', [
            'reaction' => 'like',
            'content' => 'Không được tự đánh giá sản phẩm.',
        ])->assertForbidden();
});

it('checks review eligibility in the action as well as the endpoint', function (): void {
    $product = ShopProduct::factory()->create();
    $customer = User::factory()->customer()->create();

    try {
        app(SaveShopProductReview::class)->handle($customer, $product, [
            'reaction' => 'like',
            'content' => 'Không thể bỏ qua điều kiện mua hàng.',
        ], null);
        $this->fail('The review action should reject a customer without a purchase.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    $this->assertDatabaseCount('shop_product_reviews', 0);
});

it('lets buyers delete their own review after their order is cancelled', function (): void {
    $product = ShopProduct::factory()->create();
    $customer = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();
    reviewEligibilityOrder($customer, $product, ['status' => 'cancelled', 'cancelled_at' => now()]);
    $review = ShopProductReview::factory()->create([
        'customer_id' => $customer->getKey(),
        'shop_product_id' => $product->getKey(),
        'image_url' => null,
    ]);

    $this->withToken($customer->createToken('review-delete-cancelled-order')->plainTextToken)
        ->getJson(reviewEligibilityUrl($product))
        ->assertOk()
        ->assertJsonPath('canReview', false)
        ->assertJsonPath('viewerReview.id', (string) $review->getKey());

    $this->putJson(reviewEligibilityUrl($product).'/review', [
        'reaction' => 'like',
        'content' => 'Không thể sửa khi không còn đơn hợp lệ.',
    ])->assertForbidden();

    $this->deleteJson(reviewEligibilityUrl($product).'/review')->assertOk();

    $this->assertDatabaseMissing('shop_product_reviews', ['id' => $review->getKey()]);
});

it('keeps shop replies separate from buyer review eligibility', function (): void {
    $product = ShopProduct::factory()->create();
    $review = ShopProductReview::factory()->create(['shop_product_id' => $product->getKey()]);
    $shop = $product->shopOwner;

    $this->withToken($shop->createToken('shop-review-reply')->plainTextToken)
        ->patchJson('/api/shop/reviews/'.$review->getKey().'/reply', [
            'reply' => 'Cảm ơn bạn đã mua sản phẩm.',
        ])->assertOk();

    $this->assertDatabaseHas('shop_product_reviews', [
        'id' => $review->getKey(),
        'shop_reply' => 'Cảm ơn bạn đã mua sản phẩm.',
    ]);
});
