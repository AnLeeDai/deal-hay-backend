<?php

use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\User;

it('returns public home catalog data as json', function (): void {
    $shop = User::factory()->shopOwner()->create([
        'shop_name' => 'Shop thử nghiệm',
        'shop_province' => 'Hà Nội',
        'shop_ward' => 'Cầu Giấy',
    ]);
    $category = ProductCategory::factory()->create(['name' => 'Điện tử']);

    ShopProduct::factory()
        ->count(2)
        ->for($shop, 'shopOwner')
        ->for($category, 'productCategory')
        ->create();

    $this->getJson('/api?category=Điện tử')
        ->assertOk()
        ->assertJsonPath('filters.category', 'Điện tử')
        ->assertJsonPath('categories.0', 'Điện tử')
        ->assertJsonCount(1, 'featuredShops')
        ->assertJsonCount(2, 'products.data');
});
