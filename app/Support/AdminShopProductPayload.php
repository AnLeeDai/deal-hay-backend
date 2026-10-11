<?php

namespace App\Support;

use App\Models\ShopProduct;

class AdminShopProductPayload
{
    /** @return array<string, mixed> */
    public static function from(ShopProduct $product): array
    {
        $payload = $product->toArray();

        return [
            'id' => (string) $product->id,
            'name' => (string) $product->name,
            'category' => (string) $product->productCategory->name,
            'description' => $product->description,
            'price' => (int) $product->price,
            'stock' => (int) $product->stock,
            'label' => $product->label,
            'image_url' => $payload['image_url'] ?? null,
            'is_active' => (bool) $product->is_active,
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
        ];
    }
}
