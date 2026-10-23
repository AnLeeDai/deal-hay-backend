<?php

use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\StagedImageUpload;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;

it('stages a detailed final product WebP above 20 KiB without modifying its bytes', function (): void {
    $disk = Storage::fake('r2');
    $shop = User::factory()->shopOwner()->create();
    Sanctum::actingAs($shop);
    $source = file_get_contents(base_path('tests/Fixtures/detailed-product.webp'));
    $file = UploadedFile::fake()->createWithContent('product.webp', $source);
    $token = $this->postJson('/api/private/uploads/images', [
        'uploads' => [['size' => strlen($source), 'content_type' => 'image/webp']],
    ])->assertOk()->json('uploads.0.token');
    $this->post('/api/private/uploads/images/prepare', ['token' => $token, 'image' => $file], ['Accept' => 'application/json'])
        ->assertNoContent();
    $upload = StagedImageUpload::query()->sole();
    expect($disk->get($upload->destination_path))->toBe($source);
    expect($upload->prepared_at)->not->toBeNull();
});

it('stages fifty one images for two shops independently and creates products without storage IO', function (): void {
    $disk = Storage::fake('r2');
    $category = ProductCategory::factory()->create();
    $shops = [User::factory()->shopOwner()->create(), User::factory()->shopOwner()->create()];
    $file = preparedImage('prepared.webp');
    $tokens = [];

    foreach ($shops as $shop) {
        Sanctum::actingAs($shop);
        $tokens[] = $this->postJson('/api/private/uploads/images', [
            'uploads' => array_fill(0, 51, ['size' => $file->getSize(), 'content_type' => 'image/webp']),
        ])->assertOk()->json('uploads.*.token');
    }

    // Interleave owners to verify separate records, quotas, and claim locks.
    for ($index = 0; $index < 51; $index++) {
        foreach ($shops as $shopIndex => $shop) {
            Sanctum::actingAs($shop);
            $this->post('/api/private/uploads/images/prepare', [
                'token' => $tokens[$shopIndex][$index], 'image' => $file,
            ], ['Accept' => 'application/json'])->assertNoContent();
        }
    }

    expect($disk->allFiles())->toHaveCount(102);
    expect(StagedImageUpload::whereNotNull('prepared_at')->whereNull('consumed_at')->count())->toBe(102);

    // The final request must never decode or contact R2 for prepared tokens.
    $this->mock(ImageService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('readStream', 'writeStream', 'upload');
    });
    foreach ($shops as $shopIndex => $shop) {
        Sanctum::actingAs($shop);
        $response = $this->postJson('/api/private/shop/products', [
            'name' => 'Product with full gallery', 'category' => $category->name, 'price' => 10000, 'stock' => 3,
            'primary_image_upload' => $tokens[$shopIndex][0],
            'gallery_image_uploads' => array_slice($tokens[$shopIndex], 1),
        ])->assertCreated();
        $product = ShopProduct::findOrFail($response->json('data.id'));
        expect($product->shop_owner_id)->toBe($shop->getKey());
        expect($product->images()->count())->toBe(50);
        expect($product->getRawOriginal('image_url'))->toStartWith($shop->user_code.'/products/');
    }

    expect(StagedImageUpload::whereNotNull('consumed_at')->whereNull('claimed_at')->count())->toBe(102);
    expect($disk->allFiles())->toHaveCount(102);
});

it('keeps image preparation idempotent and isolates busy claims from another shop', function (): void {
    $disk = Storage::fake('r2');
    $first = User::factory()->shopOwner()->create();
    $second = User::factory()->shopOwner()->create();
    $file = preparedImage('prepared.webp');
    $tokens = [];
    foreach ([$first, $second] as $shop) {
        Sanctum::actingAs($shop);
        $tokens[] = $this->postJson('/api/private/uploads/images', [
            'uploads' => [['size' => $file->getSize(), 'content_type' => 'image/webp']],
        ])->assertOk()->json('uploads.0.token');
    }
    $busy = StagedImageUpload::where('user_id', $first->getKey())->sole();
    $busy->update(['claimed_at' => now()]);

    Sanctum::actingAs($first);
    $this->post('/api/private/uploads/images/prepare', ['token' => $tokens[0], 'image' => $file], ['Accept' => 'application/json'])
        ->assertConflict()->assertHeader('Retry-After', '2');

    Sanctum::actingAs($second);
    $this->post('/api/private/uploads/images/prepare', ['token' => $tokens[1], 'image' => $file], ['Accept' => 'application/json'])
        ->assertNoContent();
    $files = $disk->allFiles();
    $this->post('/api/private/uploads/images/prepare', ['token' => $tokens[1], 'image' => $file], ['Accept' => 'application/json'])
        ->assertNoContent();
    expect($disk->allFiles())->toBe($files);

    $this->post('/api/private/uploads/images/prepare', ['token' => $tokens[0], 'image' => $file], ['Accept' => 'application/json'])
        ->assertUnprocessable();
    expect($busy->refresh()->consumed_at)->toBeNull();

    $busy->update(['claimed_at' => null]);
    Sanctum::actingAs($first);
    $this->post('/api/private/uploads/images/prepare', ['token' => $tokens[0], 'image' => $file], ['Accept' => 'application/json'])
        ->assertNoContent();
    expect($disk->allFiles())->toHaveCount(2);
});
