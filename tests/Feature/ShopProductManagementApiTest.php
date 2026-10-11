<?php

use App\Actions\SaveShopProductDetails;
use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

it('accepts the complete product catalog payload', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $token = $shop->createToken('shop-product-create-test')->plainTextToken;
    $primaryImage = UploadedFile::fake()->image('primary.jpg');
    $galleryImage = UploadedFile::fake()->image('gallery.webp');
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
            'Tệp ảnh đại diện không phải là hình ảnh hợp lệ.',
        );
    $this->assertDatabaseCount('shop_products', 0);
});
