<?php

use App\Actions\SaveShopProductDetails;
use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\ShopProductOption;
use App\Models\ShopProductOptionValue;
use App\Models\ShopProductVariant;
use App\Models\User;
use App\Services\MediaClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

it('accepts the complete product catalog payload', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $token = $shop->createToken('shop-product-create-test')->plainTextToken;
    $primaryImage = preparedImage('primary.webp');
    $galleryImage = preparedImage('gallery.webp');
    $product = ShopProduct::factory()
        ->for($shop, 'shopOwner')
        ->for($category, 'productCategory')
        ->make(['id' => (string) Str::uuid()]);
    $saveProduct = Mockery::mock(SaveShopProductDetails::class);
    $saveProduct->shouldReceive('create')
        ->once()
        ->withArgs(function (
            User $owner,
            array $data,
            ?UploadedFile $image,
            array $gallery,
            ?string $primaryUpload,
            array $galleryUploads,
        ) use ($shop): bool {
            return $owner->is($shop)
                && ! array_key_exists('sku', $data)
                && $data['options'][0]['name'] === 'Màu sắc'
                && $data['options'][0]['values'] === ['Đen', 'Trắng']
                && $data['variants'][0]['option_values'] === ['Màu sắc' => 'Đen']
                && ! array_key_exists('sku', $data['variants'][0])
                && $data['variants'][0]['is_default'] === true
                && $data['variants'][1]['option_values'] === ['Màu sắc' => 'Trắng']
                && ! array_key_exists('sku', $data['variants'][1])
                && $image instanceof UploadedFile
                && count($gallery) === 1
                && $primaryUpload === null
                && $galleryUploads === [];
        })
        ->andReturn($product);
    $this->app->instance(SaveShopProductDetails::class, $saveProduct);

    $response = $this->withToken($token)->post('/api/private/shop/products', [
        'name' => 'Điện thoại thử nghiệm',
        'sku' => 'user-must-not-control-this',
        'category' => $category->name,
        'price' => 1_000_000,
        'stock' => 0,
        'description' => 'Mô tả đầy đủ',
        'label' => 'Hàng mới',
        'image' => $primaryImage,
        'gallery_images' => [$galleryImage],
        'options' => [[
            'name' => 'Màu sắc',
            'values' => ['Đen', 'Trắng'],
        ]],
        'variants' => [
            [
                'sku' => 'user-must-not-control-this-variant',
                'option_values' => ['Màu sắc' => 'Đen'],
                'price' => 1_000_000,
                'stock' => 3,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'sku' => 'user-must-not-control-this-variant-either',
                'option_values' => ['Màu sắc' => 'Trắng'],
                'price' => null,
                'stock' => 2,
                'is_active' => true,
                'is_default' => false,
            ],
        ],
    ], ['Accept' => 'application/json']);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Thêm sản phẩm thành công.');
});

it('rejects a non-image product upload', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $token = $shop->createToken('shop-product-image-validation-test')->plainTextToken;

    $response = $this->withToken($token)->post('/api/private/shop/products', [
        'name' => 'Sản phẩm có tệp sai',
        'category' => $category->name,
        'price' => 1000,
        'stock' => 1,
        'image' => UploadedFile::fake()->create(
            'not-an-image.pdf',
            10,
            'application/pdf',
        ),
    ], ['Accept' => 'application/json']);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image')
        ->assertJsonPath(
            'errors.image.0',
            'Ảnh đại diện phải là WebP tĩnh đã được xử lý trên thiết bị.',
        );
    $this->assertDatabaseCount('shop_products', 0);
});

it('persists explicit variant selections and exposes active variants with their own prices and stock', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $this->partialMock(MediaClient::class)
        ->shouldReceive('upload')->once()->andReturn('products/variant-product.webp');

    $response = $this->actingAs($shop, 'sanctum')->postJson('/api/private/shop/products', [
        'name' => 'Áo có biến thể',
        'category' => $category->name,
        'price' => 50_000,
        'stock' => 99,
        'image' => preparedImage('product.webp'),
        'options' => [
            ['name' => ' Màu  sắc ', 'values' => ['Đen', 'Trắng']],
            ['name' => 'Kích thước', 'values' => ['S', 'M']],
        ],
        'variants' => [
            [
                'color' => 'Trắng',
                'size' => 'M',
                'option_values' => ['Màu sắc' => 'Đen', 'Kích thước' => 'S'],
                'price' => 55_000,
                'stock' => 8,
                'is_active' => false,
            ],
            [
                'option_values' => ['Màu sắc' => 'Trắng', 'Kích thước' => 'M'],
                'price' => null,
                'stock' => 3,
                'is_active' => true,
            ],
            [
                'option_values' => ['Màu sắc' => 'Đen', 'Kích thước' => 'M'],
                'price' => 60_000,
                'stock' => 2,
                'is_active' => true,
            ],
        ],
    ]);

    $response->assertCreated()->assertJsonPath('data.stock', 5)->assertJsonPath('data.has_variants', true);
    $product = ShopProduct::query()->findOrFail($response->json('data.id'));
    $this->assertDatabaseHas('shop_product_variants', [
        'shop_product_id' => $product->id,
        'color' => 'Đen',
        'size' => 'S',
        'stock' => 8,
        'is_active' => false,
        'is_default' => false,
    ]);
    $this->assertDatabaseHas('shop_product_variants', [
        'shop_product_id' => $product->id,
        'color' => 'Trắng',
        'size' => 'M',
        'price' => null,
        'is_default' => true,
    ]);
    $this->assertDatabaseCount('shop_inventory_movements', 3);

    $this->getJson('/api/shops/'.$shop->user_code.'/products/'.$product->slug)
        ->assertOk()
        ->assertJsonCount(2, 'product.variants')
        ->assertJsonPath('product.stock', 5)
        ->assertJsonPath('product.options.0.name', 'Màu sắc')
        ->assertJsonPath('product.options.0.values', ['Đen', 'Trắng'])
        ->assertJsonPath('product.variants.0.option_values', ['Màu sắc' => 'Trắng', 'Kích thước' => 'M'])
        ->assertJsonPath('product.variants.0.price', null)
        ->assertJsonPath('product.variants.0.stock', 3)
        ->assertJsonPath('product.variants.0.is_default', true)
        ->assertJsonPath('product.variants.1.price', 60_000);
});

it('retains option identities and combination uniqueness when updating variants without option ids', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->for($category, 'productCategory')->create([
        'price' => 50_000,
        'stock' => 3,
    ]);
    $option = ShopProductOption::factory()->for($product, 'product')->create(['name' => 'Màu sắc']);
    $value = ShopProductOptionValue::factory()->for($option, 'option')->create(['value' => 'Đen']);
    $variant = ShopProductVariant::factory()->for($product, 'product')->default()->create([
        'color' => 'Đen',
        'size' => null,
        'price' => null,
        'stock' => 3,
        'combination_key' => hash('sha256', json_encode([$value->id], JSON_THROW_ON_ERROR)),
        'option_values_snapshot' => ['Màu sắc' => 'Đen'],
    ]);
    $variant->optionValues()->attach($value);
    $this->partialMock(MediaClient::class)->shouldReceive('deleteLater')->once()->with([]);

    $response = $this->actingAs($shop, 'sanctum')->patchJson('/api/private/shop/products/'.$product->id, [
        'name' => $product->name,
        'category' => $category->name,
        'price' => 50_000,
        'stock' => 0,
        'options' => [['name' => 'Màu sắc', 'values' => ['Đen']]],
        'variants' => [[
            'id' => $variant->id,
            'option_values' => ['Màu sắc' => 'Đen'],
            'price' => null,
            'stock' => 5,
            'is_active' => true,
            'is_default' => true,
        ]],
    ]);

    $response->assertOk()->assertJsonPath('data.options.0.id', $option->id)->assertJsonPath('data.stock', 5);
    $this->assertDatabaseHas('shop_product_variant_option_values', [
        'shop_product_variant_id' => $variant->id,
        'shop_product_option_value_id' => $value->id,
    ]);
    $this->assertDatabaseHas('shop_product_variants', [
        'id' => $variant->id,
        'combination_key' => $variant->combination_key,
        'sku' => $variant->sku,
    ]);
    $this->assertDatabaseHas('shop_inventory_movements', [
        'shop_product_variant_id' => $variant->id,
        'quantity_delta' => 2,
        'stock_after' => 5,
    ]);
});

it('returns 422 for duplicate variant combinations after normalizing their names and values', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();

    $this->actingAs($shop, 'sanctum')->postJson('/api/private/shop/products', [
        'name' => 'Sản phẩm trùng biến thể',
        'category' => $category->name,
        'price' => 50_000,
        'stock' => 0,
        'image' => preparedImage('product.webp'),
        'options' => [['name' => 'Màu sắc', 'values' => ['Đen']]],
        'variants' => [
            ['option_values' => ['Màu sắc' => 'Đen'], 'stock' => 1, 'is_active' => true],
            ['option_values' => ['mau sac' => ' den '], 'stock' => 2, 'is_active' => true],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'variants.1.option_values' => 'Tổ hợp biến thể đã bị trùng.',
        ]);

    $this->assertDatabaseCount('shop_products', 0);
});

it('still accepts legacy color updates when explicit option selections are omitted', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->for($category, 'productCategory')->create();
    $option = ShopProductOption::factory()->for($product, 'product')->create(['name' => 'Màu sắc']);
    ShopProductOptionValue::factory()->for($option, 'option')->create(['value' => 'Đen', 'position' => 0]);
    ShopProductOptionValue::factory()->for($option, 'option')->create(['value' => 'Trắng', 'position' => 1]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->default()->create([
        'color' => 'Đen',
        'size' => null,
        'option_values_snapshot' => ['Màu sắc' => 'Đen'],
    ]);
    $this->partialMock(MediaClient::class)->shouldReceive('deleteLater')->once()->with([]);

    $this->actingAs($shop, 'sanctum')->patchJson('/api/private/shop/products/'.$product->id, [
        'name' => $product->name,
        'category' => $category->name,
        'price' => 50_000,
        'stock' => 0,
        'variants' => [[
            'id' => $variant->id,
            'color' => 'Trắng',
            'price' => null,
            'stock' => 3,
            'is_active' => true,
        ]],
    ])->assertOk()->assertJsonPath('data.variants.0.option_values_snapshot', ['Màu sắc' => 'Trắng']);

    $this->assertDatabaseHas('shop_product_variants', ['id' => $variant->id, 'color' => 'Trắng']);
});
