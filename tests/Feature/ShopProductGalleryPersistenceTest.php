<?php

use App\Actions\SaveShopProductDetails;
use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use App\Models\User;
use App\Services\MediaClient;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('persists fifty gallery images in one insert with ordered paths and unique model ids', function (): void {
    $this->freezeSecond();
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $file = preparedImage('gallery.webp');
    $paths = array_map(fn (int $position): string => 'products/gallery/'.$position.'.webp', range(1, 50));
    $media = $this->partialMock(MediaClient::class);
    $media->shouldReceive('upload')->once()->with($file, $shop->user_code.'/products')->andReturn('products/primary.webp');
    $media->shouldReceive('upload')->times(50)->with($file, $shop->user_code.'/products/gallery')->andReturn(...$paths);
    $media->shouldNotReceive('deleteLater');
    $mutations = captureGalleryMutations();

    $product = app(SaveShopProductDetails::class)->create($shop, galleryProductData($category), $file, array_fill(0, 50, $file));

    expect($mutations)->toHaveCount(1);
    expect($mutations->first())->toStartWith('insert into');
    expect($product->images)->toHaveCount(50);
    expect($product->images->pluck('image_url')->all())->toBe($paths);
    expect($product->images->pluck('display_order')->all())->toBe(range(1, 50));
    expect($product->images->modelKeys())->toHaveCount(50);
    expect($product->images->pluck('id')->unique())->toHaveCount(50);

    foreach ($product->images as $image) {
        expect(Str::isUuid($image->getKey(), version: 7))->toBeTrue();
        expect($image->shop_product_id)->toBe($product->getKey());
        expect($image->created_at->eq(now()))->toBeTrue();
        expect($image->updated_at->eq(now()))->toBeTrue();
    }
});

it('replaces many gallery rows with one delete and one insert while appending after retained ordering', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->for($category, 'productCategory')->create(['stock' => 0]);
    $existing = ShopProductImage::factory()->for($product, 'product')->count(50)
        ->sequence(fn ($sequence): array => ['image_url' => 'products/old/'.$sequence->index.'.webp', 'display_order' => $sequence->index + 1])
        ->create();
    $removed = $existing->take(49);
    $retained = $existing->last();
    $foreign = ShopProductImage::factory()->create();
    $file = preparedImage('gallery.webp');
    $paths = array_map(fn (int $position): string => 'products/new/'.$position.'.webp', range(1, 49));
    $media = $this->partialMock(MediaClient::class);
    $media->shouldReceive('upload')->times(49)->with($file, $shop->user_code.'/products/gallery')->andReturn(...$paths);
    $media->shouldReceive('deleteLater')->once()->with($removed->pluck('image_url')->all());
    $mutations = captureGalleryMutations();

    $updated = app(SaveShopProductDetails::class)->update(
        $shop,
        $product,
        galleryProductData($category),
        null,
        array_fill(0, 49, $file),
        [...$removed->modelKeys(), $foreign->getKey()],
    );

    expect($mutations->filter(fn (string $sql): bool => str_starts_with($sql, 'insert into')))->toHaveCount(1);
    expect($mutations->filter(fn (string $sql): bool => str_starts_with($sql, 'delete from')))->toHaveCount(1);
    expect($updated->images)->toHaveCount(50);
    expect($updated->images->first()->getKey())->toBe($retained->getKey());
    expect($updated->images->pluck('image_url')->all())->toBe([$retained->image_url, ...$paths]);
    expect($updated->images->pluck('display_order')->all())->toBe(range(50, 99));
    expect($updated->images->pluck('id')->unique())->toHaveCount(50);
    $this->assertModelExists($foreign);
    $this->assertDatabaseCount('shop_product_images', 51);
});

it('rolls gallery replacement back and cleans only new uploads when the bulk insert fails', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->for($category, 'productCategory')->create(['name' => 'Original product', 'stock' => 0]);
    $existing = ShopProductImage::factory()->for($product, 'product')->count(2)
        ->sequence(['image_url' => 'products/old/first.webp', 'display_order' => 1], ['image_url' => 'products/old/second.webp', 'display_order' => 2])
        ->create();
    $file = preparedImage('gallery.webp');
    $paths = ['products/new/first.webp', 'products/new/second.webp'];
    $media = $this->partialMock(MediaClient::class);
    $media->shouldReceive('upload')->twice()->with($file, $shop->user_code.'/products/gallery')->andReturn(...$paths);
    $media->shouldReceive('deleteLater')->once()->with($paths);
    $media->shouldReceive('discard')->once()->with(Mockery::on(fn (User $owner): bool => $owner->is($shop)), []);
    DB::listen(function (QueryExecuted $query): void {
        if (preg_match('/^insert into ["`]?shop_product_images["`]?/i', $query->sql)) {
            throw new RuntimeException('Simulated gallery insert failure.');
        }
    });

    expect(fn () => app(SaveShopProductDetails::class)->update(
        $shop,
        $product,
        galleryProductData($category),
        null,
        [$file, $file],
        $existing->modelKeys(),
    ))->toThrow(RuntimeException::class, 'Simulated gallery insert failure.');

    expect($product->refresh()->name)->toBe('Original product');
    expect($product->images()->pluck('id')->all())->toBe($existing->modelKeys());
    expect($product->images()->pluck('image_url')->all())->toBe($existing->pluck('image_url')->all());
    $this->assertDatabaseCount('shop_product_images', 2);
});

/** @return array<string, int|string> */
function galleryProductData(ProductCategory $category): array
{
    return ['name' => 'Gallery product', 'category' => $category->name, 'price' => 10_000, 'stock' => 0];
}

/** @return Collection<int, string> */
function captureGalleryMutations(): Collection
{
    $mutations = collect();
    DB::listen(function (QueryExecuted $query) use ($mutations): void {
        if (preg_match('/^(insert into|delete from) ["`]?shop_product_images["`]?/i', $query->sql)) {
            $mutations->push($query->sql);
        }
    });

    return $mutations;
}
