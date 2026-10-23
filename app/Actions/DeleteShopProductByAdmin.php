<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use App\Models\User;
use App\Notifications\ShopProductDeletedByAdminNotification;
use App\Services\MediaClient;
use App\Support\AdminShopProductPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeleteShopProductByAdmin
{
    public function __construct(
        private readonly MediaClient $cleanup,
    ) {}

    public function handle(User $shop, ShopProduct $product, string $reason): void
    {
        if ($shop->role !== UserRoleEnum::SHOP_OWNER || $product->shop_owner_id !== $shop->getKey()) {
            abort(404);
        }

        $product->loadMissing('productCategory');
        $productSnapshot = AdminShopProductPayload::from($product);
        $imagePaths = $product->images()
            ->get()
            ->map(fn ($image) => $image->getRawOriginal('image_url'))
            ->push($product->getRawOriginal('image_url'));
        $deletedNotification = new ShopProductDeletedByAdminNotification(
            productSnapshot: $productSnapshot,
            reason: $reason,
        );
        $deletedNotification->id = (string) Str::uuid();

        DB::transaction(function () use ($deletedNotification, $product, $shop, $imagePaths): void {
            $product->delete();
            $this->cleanup->deleteLater($imagePaths);
            $shop->notify($deletedNotification);
        });
    }
}
