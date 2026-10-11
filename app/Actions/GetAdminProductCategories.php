<?php

namespace App\Actions;

use App\Models\ProductCategory;

class GetAdminProductCategories
{
    /** @return list<array<string, mixed>> */
    public function handle(): array
    {
        return ProductCategory::query()
            ->withCount(['products', 'shops'])
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $category): array => [
                'id' => (string) $category->id,
                'name' => (string) $category->name,
                'is_active' => (bool) $category->is_active,
                'products_count' => (int) $category->products_count,
                'shops_count' => (int) $category->shops_count,
            ])
            ->all();
    }
}
