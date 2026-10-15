<?php

use App\Enums\TicketTypeEnum;
use App\Models\ShopProduct;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReviewedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

dataset('address profile endpoints', [
    'personal profile' => ['customer', '/api/private/profile/me', ''],
    'shop profile' => ['shopOwner', '/api/private/shop/manage', 'shop_'],
]);

it('lists backend provinces and only wards belonging to the requested province', function (): void {
    $this->getJson('/api/addresses/provinces')
        ->assertOk()
        ->assertJsonFragment(['code' => 1, 'name' => 'Thành phố Hà Nội'])
        ->assertJsonMissing(['code' => 70, 'name' => 'Phường Hoàn Kiếm']);

    $this->getJson('/api/addresses/provinces/1/wards')
        ->assertOk()
        ->assertJsonFragment(['code' => 70, 'name' => 'Phường Hoàn Kiếm'])
        ->assertJsonMissing(['code' => 1, 'name' => 'Thành phố Hà Nội']);

    $this->getJson('/api/addresses/provinces/999999/wards')->assertExactJson([]);
});

it('saves a selected backend province and its ward', function (string $state, string $endpoint, string $prefix): void {
    $user = User::factory()->{$state}()->create();
    Sanctum::actingAs($user);

    $this->patchJson($endpoint, [
        $prefix.'province' => 'Thành phố Hà Nội',
        $prefix.'ward' => 'Phường Hoàn Kiếm',
        $prefix.'address_detail' => '12 Hàng Bài',
    ])->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->getKey(),
        $prefix.'province' => 'Thành phố Hà Nội',
        $prefix.'ward' => 'Phường Hoàn Kiếm',
        $prefix.'address_detail' => '12 Hàng Bài',
    ]);
})->with('address profile endpoints');

it('returns 422 for an arbitrary province and does not save an address', function (string $state, string $endpoint, string $prefix): void {
    $user = User::factory()->{$state}()->create();
    Sanctum::actingAs($user);

    $this->patchJson($endpoint, [
        $prefix.'province' => 'Tỉnh tự điền',
        $prefix.'ward' => 'Phường Hoàn Kiếm',
    ])->assertUnprocessable()->assertJsonValidationErrors([$prefix.'province', $prefix.'ward'])
        ->assertJsonPath('errors.'.$prefix.'province.0', 'Vui lòng chọn tỉnh/thành từ danh sách.');

    $this->assertDatabaseHas('users', ['id' => $user->getKey(), $prefix.'province' => null, $prefix.'ward' => null]);
})->with('address profile endpoints');

it('returns 422 when a real ward does not belong to the selected province', function (string $state, string $endpoint, string $prefix): void {
    $user = User::factory()->{$state}()->create();
    Sanctum::actingAs($user);

    $this->patchJson($endpoint, [
        $prefix.'province' => 'Tỉnh Cao Bằng',
        $prefix.'ward' => 'Phường Hoàn Kiếm',
    ])->assertUnprocessable()->assertJsonValidationErrors([$prefix.'ward'])
        ->assertJsonPath('errors.'.$prefix.'ward.0', 'Vui lòng chọn phường/xã thuộc tỉnh/thành đã chọn.');

    $this->assertDatabaseHas('users', ['id' => $user->getKey(), $prefix.'province' => null, $prefix.'ward' => null]);
})->with('address profile endpoints');

it('returns 422 for a province-only patch that would leave a mismatching saved ward', function (string $state, string $endpoint, string $prefix): void {
    $user = User::factory()->{$state}()->create([
        $prefix.'province' => 'Thành phố Hà Nội',
        $prefix.'ward' => 'Phường Hoàn Kiếm',
    ]);
    Sanctum::actingAs($user);

    $this->patchJson($endpoint, [$prefix.'province' => 'Tỉnh Cao Bằng'])
        ->assertUnprocessable()->assertJsonValidationErrors([$prefix.'ward']);

    $this->assertDatabaseHas('users', ['id' => $user->getKey(), $prefix.'province' => 'Thành phố Hà Nội']);
})->with('address profile endpoints');

it('returns 422 for a street-only address without a selected province and ward', function (string $state, string $endpoint, string $prefix): void {
    $user = User::factory()->{$state}()->create();
    Sanctum::actingAs($user);

    $this->patchJson($endpoint, [$prefix.'address_detail' => '12 Hàng Bài'])
        ->assertUnprocessable()->assertJsonValidationErrors([$prefix.'province', $prefix.'ward']);

    $this->assertDatabaseHas('users', ['id' => $user->getKey(), $prefix.'address_detail' => null]);
})->with('address profile endpoints');

it('allows unrelated profile edits when an address has not been provided', function (): void {
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/private/profile/me', ['full_name' => 'Nguyễn An'])->assertOk();

    $this->assertDatabaseHas('users', ['id' => $user->getKey(), 'full_name' => 'Nguyễn An', 'province' => null, 'ward' => null]);
});

it('returns 422 for a shop upgrade containing an arbitrary address', function (): void {
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/shop-owner-upgrade-requests', [
        'full_name' => $user->full_name,
        'shop_name' => 'Shop kiểm tra địa chỉ',
        'email' => $user->email,
        'shop_email' => 'address-shop@example.com',
        'phone_number' => '0987654321',
        'shop_phone_number' => '0912345678',
        'province' => 'Tỉnh tự điền',
        'ward' => 'Phường tự điền',
        'address_detail' => '12 Hàng Bài',
        'message' => '',
    ])->assertUnprocessable()->assertJsonValidationErrors(['province', 'ward']);

    $this->assertDatabaseMissing('tickets', ['user_id' => $user->getKey(), 'type' => TicketTypeEnum::ShopOwnerUpgrade]);
});

it('requires structured address choices before creating a shop order', function (): void {
    $shop = User::factory()->shopOwner()->create();
    Sanctum::actingAs($shop);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/private/shop/orders', [
        'customer_name' => 'Nguyễn An',
        'customer_phone' => '0987654321',
        'shipping_address' => 'Địa chỉ tự điền',
        'items' => [['sku' => 'PRODUCT-1', 'quantity' => 1]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['shipping_province_code', 'shipping_ward_code']);

    $this->assertDatabaseCount('shop_orders', 0);
});

it('saves the backend ward and province names in shop order shipping addresses', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['sku' => 'ADDRESS-1', 'stock' => 5, 'price' => 100000]);
    Sanctum::actingAs($shop);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/private/shop/orders', [
        'customer_name' => 'Nguyễn An',
        'customer_phone' => '0987654321',
        'shipping_address' => '12 Hàng Bài',
        'shipping_province_code' => 1,
        'shipping_ward_code' => 70,
        'items' => [['sku' => $product->sku, 'quantity' => 1]],
    ])->assertCreated();

    $this->assertDatabaseHas('shop_orders', [
        'shop_owner_id' => $shop->getKey(),
        'shipping_address' => '12 Hàng Bài, Phường Hoàn Kiếm, Thành phố Hà Nội',
    ]);
});

it('returns 422 for an invalid delivery phone before creating a shop order', function (): void {
    $shop = User::factory()->shopOwner()->create();
    Sanctum::actingAs($shop);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/private/shop/orders', [
        'customer_name' => 'Nguyễn An',
        'customer_phone' => 'abc',
        'shipping_address' => '12 Hàng Bài',
        'shipping_province_code' => 1,
        'shipping_ward_code' => 70,
        'items' => [['sku' => 'ADDRESS-1', 'quantity' => 1]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['customer_phone']);

    $this->assertDatabaseCount('shop_orders', 0);
});

it('returns 422 before approving a legacy upgrade ticket with a mismatching address', function (): void {
    $customer = User::factory()->customer()->create();
    $admin = User::query()->where('role', 'administrator')->sole();
    $ticket = Ticket::query()->create([
        'user_id' => $customer->getKey(),
        'type' => TicketTypeEnum::ShopOwnerUpgrade,
        'source' => 'user_menu',
        'message' => '',
        'metadata' => ['shop_owner_upgrade' => ['province' => 'Tỉnh Cao Bằng', 'ward' => 'Phường Hoàn Kiếm']],
        'status' => 'pending',
    ]);
    Sanctum::actingAs($admin);

    $this->patchJson('/api/admin/tickets/'.$ticket->getKey(), ['status' => 'approved'])
        ->assertUnprocessable()->assertJsonValidationErrors(['general']);

    $this->assertDatabaseHas('tickets', ['id' => $ticket->getKey(), 'status' => 'pending', 'reviewed_by' => null]);
    $this->assertDatabaseHas('users', ['id' => $customer->getKey(), 'role' => 'customer', 'province' => null, 'ward' => null]);
    $this->assertDatabaseCount('notifications', 0);
});

it('approves an upgrade with matching reference addresses and notifies the applicant', function (): void {
    $customer = User::factory()->customer()->create();
    $admin = User::query()->where('role', 'administrator')->sole();
    $ticket = Ticket::query()->create([
        'user_id' => $customer->getKey(),
        'type' => TicketTypeEnum::ShopOwnerUpgrade,
        'source' => 'user_menu',
        'message' => '',
        'metadata' => ['shop_owner_upgrade' => [
            'full_name' => $customer->full_name,
            'shop_name' => 'Shop kiểm tra địa chỉ',
            'email' => $customer->email,
            'shop_email' => 'address-shop@example.com',
            'phone_number' => '0987654321',
            'shop_phone_number' => '0912345678',
            'province' => 'Thành phố Hà Nội',
            'ward' => 'Phường Hoàn Kiếm',
            'address_detail' => '12 Hàng Bài',
        ]],
        'status' => 'pending',
    ]);
    Notification::fake();
    Sanctum::actingAs($admin);

    $this->patchJson('/api/admin/tickets/'.$ticket->getKey(), ['status' => 'approved'])->assertOk();

    $this->assertDatabaseHas('tickets', ['id' => $ticket->getKey(), 'status' => 'approved', 'reviewed_by' => $admin->getKey()]);
    $this->assertDatabaseHas('users', ['id' => $customer->getKey(), 'role' => 'shop_owner', 'province' => 'Thành phố Hà Nội', 'ward' => 'Phường Hoàn Kiếm']);
    Notification::assertSentTo($customer, TicketReviewedNotification::class);
});
