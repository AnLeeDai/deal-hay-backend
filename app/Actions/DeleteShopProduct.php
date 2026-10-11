<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\MediaClient;
use Illuminate\Support\Facades\DB;

class DeleteShopProduct
{
    public function __construct(private readonly MediaClient $cleanup) {}

    public function handle(User $shopOwner, ShopProduct $product): bool
    {
        if ($shopOwner->role !== UserRoleEnum::SHOP_OWNER || $product->shop_owner_id !== $shopOwner->getKey()) {
            abort(403, 'Bạn không có quyền xoá sản phẩm này.');
        }

        if ($product->orderItems()->exists()) {
            return false;
        }

        $imagePaths = $product->images
            ->map(fn ($image) => $image->getRawOriginal('image_url'))
            ->push($product->getRawOriginal('image_url'));

        DB::transaction(function () use ($product, $imagePaths): void {
            $product->delete();
            $this->cleanup->deleteLater($imagePaths);
        });

        return true;
    }
}
