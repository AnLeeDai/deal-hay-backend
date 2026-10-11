<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReorderShopCategories
{
    /** @param list<string> $categoryIds */
    public function handle(User $shopOwner, array $categoryIds): void
    {
        DB::transaction(function () use ($categoryIds, $shopOwner): void {
            foreach ($categoryIds as $index => $categoryId) {
                $shopOwner->productCategories()->syncWithoutDetaching([
                    $categoryId => ['display_order' => $index + 1],
                ]);
            }
        });
    }
}
