<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateShopProductPricingRequest;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ShopProductPricingController extends Controller
{
    public function update(UpdateShopProductPricingRequest $request, ShopProduct $shopProduct): JsonResponse
    {
        DB::transaction(function () use ($request, $shopProduct): void {
            User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $product = ShopProduct::query()->where('shop_owner_id', $request->user()->getKey())->lockForUpdate()->findOrFail($shopProduct->getKey());
            $product->update($request->safe()->only(['price', 'old_price', 'video_url']));
        }, attempts: 5);

        return response()->json(['message' => 'Đã cập nhật giá và thông tin sản phẩm.']);
    }
}
