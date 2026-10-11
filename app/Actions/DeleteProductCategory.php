<?php

namespace App\Actions;

use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class DeleteProductCategory
{
    public function handle(ProductCategory $category): bool
    {
        return DB::transaction(function () use ($category): bool {
            $lockedCategory = ProductCategory::query()
                ->lockForUpdate()
                ->findOrFail($category->getKey());

            if ($lockedCategory->products()->exists()) {
                return false;
            }

            $lockedCategory->delete();

            return true;
        });
    }
}
