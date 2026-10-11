<?php

namespace App\Actions;

use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\ShopProductOption;
use App\Models\ShopProductOptionValue;
use App\Models\ShopProductVariant;
use App\Models\User;
use App\Services\MediaClient;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaveShopProductDetails
{
    public function __construct(
        private readonly MediaClient $images,
        private readonly SyncShopProductInventory $inventory,
        private readonly MediaClient $stagedUploads,
        private readonly MediaClient $cleanup,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $galleryImages
     */
    public function create(User $shopOwner, array $data, ?UploadedFile $primaryImage, array $galleryImages, ?string $primaryUpload = null, array $galleryUploads = []): ShopProduct
    {
        $uploadedPaths = [];

        try {
            $primaryImagePath = $primaryUpload
                ? $this->stagedUploads->consume($shopOwner, $primaryUpload, $shopOwner->user_code.'/products')
                : $this->images->upload($primaryImage, $shopOwner->user_code.'/products');
            $uploadedPaths[] = $primaryImagePath;
            $galleryPaths = $this->prepareGalleryImages(
                $galleryImages,
                $galleryUploads,
                $shopOwner,
                $uploadedPaths,
            );

            return DB::transaction(function () use ($data, $galleryPaths, $primaryImagePath, $shopOwner): ShopProduct {
                $lockedShopOwner = User::query()->lockForUpdate()->findOrFail($shopOwner->getKey());
                $uploadedAt = now();
                $variants = $this->variants($data);
                $sku = filled($data['sku'] ?? null)
                    ? $this->normalizedSku((string) $data['sku'])
                    : $this->nextProductSku($lockedShopOwner, $uploadedAt);
                $this->assertSkuAvailable($lockedShopOwner, $sku);
                $product = ShopProduct::query()->create([
                    ...$this->productData($data),
                    'shop_owner_id' => $lockedShopOwner->getKey(),
                    'sku' => $sku,
                    'sku_normalized' => $sku,
                    'stock' => $variants === []
                        ? (int) $data['stock']
                        : (int) collect($variants)->where('is_active', true)->sum('stock'),
                    'has_variants' => $variants !== [],
                    'image_url' => $primaryImagePath,
                    'is_active' => true,
                ]);
                $this->syncOptions($product, $this->options($data));

                if ($variants === []) {
                    $this->inventory->recordInitial($product, null, $product->stock, $lockedShopOwner);
                } else {
                    $this->syncVariants($product, $variants, $lockedShopOwner);
                }

                $this->addGalleryPaths($product, $galleryPaths);

                return $product->load([
                    'images',
                    'options.values',
                    'variants.optionValues.option',
                ]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->cleanupFailedUpload($shopOwner, $uploadedPaths, $primaryUpload, $galleryUploads);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $galleryImages
     * @param  list<string>  $removeImageIds
     */
    public function update(
        User $shopOwner,
        ShopProduct $product,
        array $data,
        ?UploadedFile $primaryImage,
        array $galleryImages,
        array $removeImageIds,
        ?string $primaryUpload = null,
        array $galleryUploads = [],
    ): ShopProduct {
        $uploadedPaths = [];
        $obsoletePaths = [];

        try {
            $newPrimaryImage = null;

            if ($primaryImage instanceof UploadedFile || $primaryUpload) {
                $newPrimaryImage = $primaryUpload
                    ? $this->stagedUploads->consume($shopOwner, $primaryUpload, $shopOwner->user_code.'/products')
                    : $this->images->upload($primaryImage, $shopOwner->user_code.'/products');
                $uploadedPaths[] = $newPrimaryImage;
            }

            $galleryPaths = $this->prepareGalleryImages(
                $galleryImages,
                $galleryUploads,
                $shopOwner,
                $uploadedPaths,
            );

            $updatedProduct = DB::transaction(function () use ($data, $galleryPaths, $newPrimaryImage, $product, $removeImageIds, $shopOwner, &$obsoletePaths): ShopProduct {
                $lockedShopOwner = User::query()->lockForUpdate()->findOrFail($shopOwner->getKey());
                $lockedProduct = ShopProduct::query()
                    ->whereBelongsTo($lockedShopOwner, 'shopOwner')
                    ->lockForUpdate()
                    ->findOrFail($product->getKey());
                $variants = $this->variants($data);
                $previousStock = (int) $lockedProduct->stock;
                $nextStock = $variants === []
                    ? (int) $data['stock']
                    : (int) collect($variants)->where('is_active', true)->sum('stock');
                $productData = [...$this->productData($data), 'stock' => $nextStock];
                $sku = filled($data['sku'] ?? null)
                    ? $this->normalizedSku((string) $data['sku'])
                    : (filled($lockedProduct->sku)
                        ? $this->normalizedSku((string) $lockedProduct->sku)
                        : $this->nextProductSku(
                            $lockedShopOwner,
                            $lockedProduct->created_at ?? now(),
                        ));
                $this->assertSkuAvailable($lockedShopOwner, $sku, product: $lockedProduct);
                $productData['sku'] = $sku;
                $productData['sku_normalized'] = $sku;

                $removedImageCount = $lockedProduct->images()->whereKey($removeImageIds)->count();
                $finalGalleryCount = $lockedProduct->images()->count() - $removedImageCount + count($galleryPaths);

                if ($finalGalleryCount > 50) {
                    throw ValidationException::withMessages([
                        'gallery_images' => 'Mỗi sản phẩm chỉ được có tối đa 50 ảnh bổ sung.',
                    ]);
                }

                if ($newPrimaryImage !== null) {
                    $obsoletePaths[] = $lockedProduct->getRawOriginal('image_url');
                    $productData['image_url'] = $newPrimaryImage;
                }

                $lockedProduct->update($productData);
                $this->snapshotVariantOptions($lockedProduct);
                $this->syncOptions($lockedProduct, $this->options($data));

                if ($variants === []) {
                    $this->removeVariants($lockedProduct);
                    $lockedProduct->update(['has_variants' => false]);
                    $this->inventory->reconcile($lockedProduct, null, $previousStock, $nextStock, $lockedShopOwner);
                } else {
                    $this->syncVariants($lockedProduct, $variants, $lockedShopOwner);
                    $lockedProduct->update([
                        'stock' => (int) $lockedProduct->variants()->where('is_active', true)->sum('stock'),
                        'has_variants' => true,
                    ]);
                }

                $this->removeGalleryImages($lockedProduct, $removeImageIds, $obsoletePaths);
                $this->addGalleryPaths($lockedProduct, $galleryPaths);
                $this->cleanup->deleteLater($obsoletePaths);

                return $lockedProduct->load([
                    'images',
                    'options.values',
                    'variants.optionValues.option',
                ]);
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->cleanupFailedUpload($shopOwner, $uploadedPaths, $primaryUpload, $galleryUploads);

            throw $exception;
        }

        return $updatedProduct;
    }

    /** @param list<string> $uploadedPaths @param list<string> $galleryUploads */
    private function cleanupFailedUpload(User $shopOwner, array $uploadedPaths, ?string $primaryUpload, array $galleryUploads): void
    {
        $this->cleanup->deleteLater($uploadedPaths);

        try {
            $this->stagedUploads->discard($shopOwner, array_filter([
                $primaryUpload,
                ...$galleryUploads,
            ]));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function productData(array $data): array
    {
        return [
            ...Arr::only($data, ['name', 'description', 'price', 'label']),
            'old_price' => null,
            'product_category_id' => ProductCategory::query()
                ->where('name', $data['category'])
                ->where('is_active', true)
                ->valueOrFail('id'),
        ];
    }

    /** @param array<string, mixed> $data @return list<array<string, mixed>> */
    private function variants(array $data): array
    {
        return array_values($data['variants'] ?? []);
    }

    /** @param array<string, mixed> $data @return list<array<string, mixed>> */
    private function options(array $data): array
    {
        return array_values($data['options'] ?? []);
    }

    /** @param list<array<string, mixed>> $options */
    private function syncOptions(ShopProduct $product, array $options): void
    {
        $existingOptions = $product->options()
            ->with('values')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $retainedOptionIds = [];

        foreach ($existingOptions as $existingOption) {
            $temporaryName = '__sync__'.Str::lower((string) $existingOption->getKey());
            ShopProductOption::query()
                ->whereKey($existingOption->getKey())
                ->update(['name_normalized' => $temporaryName]);
            $existingOption->name_normalized = $temporaryName;
            $existingOption->syncOriginalAttribute('name_normalized');
        }

        foreach ($options as $position => $optionData) {
            $optionId = filled($optionData['id'] ?? null) ? (string) $optionData['id'] : null;
            $name = Str::squish((string) $optionData['name']);
            $nameNormalized = $this->catalogKey($name);
            $option = $optionId !== null
                ? $existingOptions->firstWhere('id', $optionId)
                : $existingOptions->firstWhere('name_normalized', $nameNormalized);
            $payload = [
                'name' => $name,
                'name_normalized' => $nameNormalized,
                'position' => $position,
            ];

            if ($option instanceof ShopProductOption) {
                $option->update($payload);
            } else {
                $option = $product->options()->create($payload);
            }

            $retainedOptionIds[] = (string) $option->getKey();
            $existingValues = $option->values->keyBy('value_normalized');
            $retainedValueIds = [];

            foreach (array_values((array) $optionData['values']) as $valuePosition => $value) {
                $normalizedValue = $this->catalogKey((string) $value);
                $optionValue = $existingValues->get($normalizedValue);
                $valuePayload = [
                    'value' => Str::squish((string) $value),
                    'value_normalized' => $normalizedValue,
                    'position' => $valuePosition,
                ];

                if ($optionValue instanceof ShopProductOptionValue) {
                    $optionValue->update($valuePayload);
                } else {
                    $optionValue = $option->values()->create($valuePayload);
                }

                $retainedValueIds[] = (string) $optionValue->getKey();
            }

            $option->values()->whereNotIn('id', $retainedValueIds)->delete();
        }

        $product->options()->whereNotIn('id', $retainedOptionIds)->delete();
        $product->unsetRelation('options');
        $product->load('options.values');
    }

    /** @param list<array<string, mixed>> $variants */
    private function syncVariants(ShopProduct $product, array $variants, User $changedBy): void
    {
        $product->unsetRelation('options');
        $product->load('options.values');
        $existingVariants = $product->variants()
            ->with('optionValues.option')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (ShopProductVariant $variant): string => (string) $variant->id);
        $product->variants()
            ->whereKey($existingVariants->keys())
            ->update(['combination_key' => null]);
        $usedSkus = [$this->normalizedSku((string) $product->sku)];
        $retainedIds = [];

        foreach ($variants as $position => $variantData) {
            $variantId = filled($variantData['id'] ?? null) ? (string) $variantData['id'] : null;
            $variant = $variantId !== null ? $existingVariants->get($variantId) : null;
            $requestedSku = filled($variantData['sku'] ?? null)
                ? $this->normalizedSku((string) $variantData['sku'])
                : null;
            $sku = $requestedSku
                ?? ($variant instanceof ShopProductVariant
                    ? $this->normalizedSku((string) $variant->sku)
                    : $this->nextVariantSku($product, $usedSkus, $changedBy));

            if (in_array($sku, $usedSkus, true)) {
                throw ValidationException::withMessages([
                    "variants.{$position}.sku" => 'SKU đã được sử dụng trong sản phẩm.',
                ]);
            }

            $this->assertSkuAvailable($changedBy, $sku, $product, $variant);
            $usedSkus[] = $sku;
            [$optionValueIds, $canonicalOptionValues, $combinationKey] = $this->variantSelection(
                $product,
                (array) $variantData['option_values'],
            );
            $payload = [
                ...Arr::only($variantData, ['color', 'size', 'price', 'stock', 'is_active', 'is_default']),
                'shop_owner_id' => $changedBy->getKey(),
                'sku' => $sku,
                'sku_normalized' => $sku,
                'combination_key' => $combinationKey,
                'option_values_snapshot' => $canonicalOptionValues,
                'position' => $position,
                'stock' => (int) $variantData['stock'],
                'retired_at' => null,
                'color' => $this->legacyOptionSelection(
                    $canonicalOptionValues,
                    ['Màu sắc', 'Mau sac', 'Color'],
                ) ?? ($variantData['color'] ?? null),
                'size' => $this->legacyOptionSelection(
                    $canonicalOptionValues,
                    ['Kích thước', 'Kich thuoc', 'Size'],
                ) ?? ($variantData['size'] ?? null),
            ];

            if ($variant instanceof ShopProductVariant) {
                $previousStock = (int) $variant->stock;
                $variant->update($payload);
                $variant->optionValues()->sync($optionValueIds);
                $this->inventory->reconcile($product, $variant, $previousStock, (int) $variant->stock, $changedBy);
            } else {
                $variant = $product->variants()->create($payload);
                $variant->optionValues()->sync($optionValueIds);
                $this->inventory->recordInitial($product, $variant, (int) $variant->stock, $changedBy);
            }

            $retainedIds[] = (string) $variant->id;
        }

        foreach ($existingVariants->whereNotIn('id', $retainedIds) as $variant) {
            if ((int) $variant->stock > 0
                || $variant->orderItems()->exists()
                || $variant->inventoryMovements()->exists()
                || $variant->chatMessages()->exists()) {
                $variant->update([
                    'combination_key' => null,
                    'is_active' => false,
                    'is_default' => false,
                    'retired_at' => now(),
                ]);

                continue;
            }

            $variant->delete();
        }
    }

    /** @param array<string, mixed> $optionValues @return array{list<string>, array<string, string>, string} */
    private function variantSelection(ShopProduct $product, array $optionValues): array
    {
        $providedValues = collect($optionValues)->mapWithKeys(fn (mixed $value, mixed $name): array => [
            $this->catalogKey((string) $name) => $this->catalogKey((string) $value),
        ]);
        $selectedIds = [];
        $canonicalValues = [];

        foreach ($product->options as $option) {
            $valueKey = $providedValues->get((string) $option->name_normalized);
            $optionValue = $option->values->firstWhere('value_normalized', $valueKey);

            if (! $optionValue instanceof ShopProductOptionValue) {
                throw ValidationException::withMessages([
                    'variants' => "Giá trị của thuộc tính {$option->name} không hợp lệ.",
                ]);
            }

            $selectedIds[] = (string) $optionValue->getKey();
            $canonicalValues[(string) $option->name] = (string) $optionValue->value;
        }

        return [
            $selectedIds,
            $canonicalValues,
            hash('sha256', json_encode($selectedIds, JSON_THROW_ON_ERROR)),
        ];
    }

    /** @param array<string, string> $optionValues @param list<string> $aliases */
    private function legacyOptionSelection(array $optionValues, array $aliases): ?string
    {
        $normalizedAliases = collect($aliases)->map(fn (string $alias): string => $this->catalogKey($alias));

        foreach ($optionValues as $name => $value) {
            if ($normalizedAliases->contains($this->catalogKey($name))) {
                return $value;
            }
        }

        return null;
    }

    private function nextProductSku(User $shopOwner, CarbonInterface $uploadedAt): string
    {
        $base = Str::upper((string) $shopOwner->user_code).'-'.$uploadedAt->format('dmY');
        $sequence = ShopProduct::query()
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->whereBetween('created_at', [
                $uploadedAt->copy()->startOfDay(),
                $uploadedAt->copy()->endOfDay(),
            ])
            ->count() + 1;

        do {
            $suffix = '-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sku = Str::substr($base, 0, 80 - strlen($suffix)).$suffix;
            $sequence++;
        } while ($this->skuExists($shopOwner, $sku));

        return $sku;
    }

    /** @param list<string> $usedSkus */
    private function nextVariantSku(ShopProduct $product, array $usedSkus, User $shopOwner): string
    {
        $sequence = 1;

        do {
            $suffix = '-V'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $prefix = Str::substr(
                Str::upper((string) $product->sku),
                0,
                80 - strlen($suffix),
            );
            $sku = $prefix.$suffix;
            $sequence++;
        } while (in_array($sku, $usedSkus, true) || $this->skuExists($shopOwner, $sku));

        return $sku;
    }

    private function assertSkuAvailable(
        User $shopOwner,
        string $sku,
        ?ShopProduct $product = null,
        ?ShopProductVariant $variant = null,
    ): void {
        $productConflict = ShopProduct::query()
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->where('sku_normalized', $sku)
            ->when($product, fn ($query) => $query->whereKeyNot($product->getKey()))
            ->exists();
        $variantConflict = ShopProductVariant::query()
            ->where(fn ($query) => $query
                ->whereBelongsTo($shopOwner, 'shopOwner')
                ->orWhereHas('product', fn ($query) => $query->whereBelongsTo($shopOwner, 'shopOwner')))
            ->where('sku_normalized', $sku)
            ->when($variant, fn ($query) => $query->whereKeyNot($variant->getKey()))
            ->exists();

        if ($productConflict || $variantConflict) {
            throw ValidationException::withMessages([
                'sku' => 'SKU đã được sử dụng trong cửa hàng.',
            ]);
        }
    }

    private function skuExists(User $shopOwner, string $sku): bool
    {
        return ShopProduct::query()
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->where('sku_normalized', $sku)
            ->exists()
            || ShopProductVariant::query()
                ->where(fn ($query) => $query
                    ->whereBelongsTo($shopOwner, 'shopOwner')
                    ->orWhereHas('product', fn ($query) => $query->whereBelongsTo($shopOwner, 'shopOwner')))
                ->where('sku_normalized', $sku)
                ->exists();
    }

    private function normalizedSku(string $sku): string
    {
        return Str::upper(trim($sku));
    }

    private function catalogKey(string $value): string
    {
        return Str::lower(Str::ascii(Str::squish($value)));
    }

    private function snapshotVariantOptions(ShopProduct $product): void
    {
        $variants = $product->variants()
            ->with('optionValues.option')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($variants as $variant) {
            $optionValues = $variant->optionValueMap();

            if ($optionValues === []) {
                continue;
            }

            ShopProductVariant::query()
                ->whereKey($variant->getKey())
                ->update([
                    'option_values_snapshot' => json_encode($optionValues, JSON_THROW_ON_ERROR),
                ]);
        }
    }

    private function removeVariants(ShopProduct $product): void
    {
        $variants = $product->allVariants()->orderBy('id')->lockForUpdate()->get();
        $unsafeVariant = $variants->first(fn (ShopProductVariant $variant): bool => (int) $variant->stock > 0
            || $variant->orderItems()->exists()
            || $variant->inventoryMovements()->exists()
            || $variant->chatMessages()->exists());

        if ($unsafeVariant instanceof ShopProductVariant) {
            throw ValidationException::withMessages([
                'variants' => 'Không thể chuyển sang sản phẩm đơn khi biến thể còn tồn kho hoặc lịch sử giao dịch. Hãy giữ biến thể và ngừng bán nếu cần.',
            ]);
        }

        foreach ($variants as $variant) {
            $variant->delete();
        }
    }

    /** @param list<UploadedFile> $images @param list<string> $tokens @param list<string> $uploadedPaths @return list<string> */
    private function prepareGalleryImages(
        array $images,
        array $tokens,
        User $shopOwner,
        array &$uploadedPaths,
    ): array {
        $galleryPaths = [];

        foreach ($images as $image) {
            $path = $this->images->upload(
                $image,
                $shopOwner->user_code.'/products/gallery',
            );
            $galleryPaths[] = $path;
            $uploadedPaths[] = $path;
        }

        foreach ($tokens as $token) {
            $path = $this->stagedUploads->consume(
                $shopOwner,
                $token,
                $shopOwner->user_code.'/products/gallery',
            );
            $galleryPaths[] = $path;
            $uploadedPaths[] = $path;
        }

        return $galleryPaths;
    }

    /** @param list<string> $paths */
    private function addGalleryPaths(ShopProduct $product, array $paths): void
    {
        $displayOrder = (int) $product->images()->max('display_order');

        foreach ($paths as $path) {
            $product->images()->create([
                'image_url' => $path,
                'display_order' => ++$displayOrder,
            ]);
        }
    }

    /** @param list<string> $imageIds @param list<string> $obsoletePaths */
    private function removeGalleryImages(ShopProduct $product, array $imageIds, array &$obsoletePaths): void
    {
        $images = $product->images()->whereKey($imageIds)->get();

        foreach ($images as $image) {
            $obsoletePaths[] = $image->getRawOriginal('image_url');
            $image->delete();
        }
    }
}
