<?php

namespace App\Actions;

use App\Models\ProductCategory;

class SaveProductCategory
{
    /** @param array{name: string} $data */
    public function handle(array $data, ?ProductCategory $category = null): ProductCategory
    {
        if ($category === null) {
            return ProductCategory::query()->create($data);
        }

        $category->update($data);

        return $category;
    }
}
