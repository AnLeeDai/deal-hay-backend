<?php

namespace App\Support;

use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use App\Models\ShopProductOption;
use App\Models\ShopProductVariant;

class PublicShopProductPayload
{
    /** @return array<string, mixed> */
    public static function forHome(ShopProduct $product): array
    {
        $shopOwner = $product->shopOwner;
        $payload = $product->toArray();
        $shopPayload = $shopOwner->toArray();

        return [
            'id' => (string) $product->id,
            'slug' => (string) $product->slug,
            'name' => (string) $product->name,
            'category' => (string) $product->productCategory->name,
            'price' => (int) $product->price,
            'stock' => (int) $product->stock,
            'has_variants' => (bool) $product->has_variants,
            'label' => $product->label,
            'image_url' => $payload['image_url'],
            'shop' => [
                'code' => (string) $shopOwner->user_code,
                'name' => (string) ($shopOwner->shop_name ?: 'Cửa hàng DealHay'),
                'avatar_url' => $shopPayload['shop_avatar_url'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function forShop(ShopProduct $product): array
    {
        $payload = $product->toArray();

        return [
            'id' => (string) $product->id,
            'slug' => (string) $product->slug,
            'sku' => $product->sku,
            'name' => (string) $product->name,
            'category' => (string) $product->productCategory->name,
            'description' => $product->description,
            'price' => (int) $product->price,
            'stock' => (int) $product->stock,
            'has_variants' => (bool) $product->has_variants,
            'label' => $product->label,
            'image_url' => $payload['image_url'],
            'images' => $product->relationLoaded('images')
                ? $product->images->map(function (ShopProductImage $image): array {
                    $imagePayload = $image->toArray();

                    return [
                        'id' => (string) $image->id,
                        'image_url' => (string) $imagePayload['image_url'],
                    ];
                })->all()
                : [],
            'options' => $product->relationLoaded('options')
                ? $product->options->map(fn (ShopProductOption $option): array => [
                    'id' => (string) $option->id,
                    'name' => (string) $option->name,
                    'values' => $option->values->pluck('value')->map('strval')->all(),
                ])->all()
                : [],
            'variants' => $product->relationLoaded('variants')
                ? $product->variants->map(fn (ShopProductVariant $variant): array => [
                    'id' => (string) $variant->id,
                    'sku' => (string) $variant->sku,
                    'color' => $variant->color,
                    'size' => $variant->size,
                    'option_values' => $variant->optionValueMap(),
                    'price' => $variant->price !== null ? (int) $variant->price : null,
                    'stock' => (int) $variant->stock,
                    'is_active' => (bool) $variant->is_active,
                    'is_default' => (bool) $variant->is_default,
                ])->all()
                : [],
        ];
    }
}
