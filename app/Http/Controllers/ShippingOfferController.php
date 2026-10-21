<?php

namespace App\Http\Controllers;

use App\Http\Requests\RedeemShippingRewardRequest;
use App\Http\Requests\ShippingOfferRequest;
use App\Models\ShippingRewardView;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\ShippingRewardService;
use Illuminate\Http\JsonResponse;

class ShippingOfferController extends Controller
{
    public function show(User $shop, ShopProduct $shopProduct, ShippingRewardService $rewards): JsonResponse
    {
        return response()->json(['shipping_offer' => $rewards->offer($shop, $shopProduct)]);
    }

    public function code(ShippingOfferRequest $request, User $shop, ShopProduct $shopProduct, ShippingRewardService $rewards): JsonResponse
    {
        $rewards->assertPublicProduct($shop, $shopProduct);
        $code = $rewards->currentCode($request->user(), $shopProduct);

        return response()->json(['shipping_discount_code' => $code ? $rewards->payload($code) : null]);
    }

    public function start(ShippingOfferRequest $request, User $shop, ShopProduct $shopProduct, ShippingRewardService $rewards): JsonResponse
    {
        $view = $rewards->start($request->user(), $shop, $shopProduct);

        return response()->json(['view' => [
            'id' => $view->getKey(),
            'expires_at' => $view->expires_at->toISOString(),
            'rewarded_ad_unit_path' => $view->rewarded_ad_unit_path,
        ]], 201);
    }

    public function redeem(RedeemShippingRewardRequest $request, User $shop, ShopProduct $shopProduct, ShippingRewardView $shippingRewardView, ShippingRewardService $rewards): JsonResponse
    {
        $code = $rewards->redeem($request->user(), $shop, $shopProduct, $shippingRewardView);

        return response()->json([
            'message' => 'Đã nhận mã miễn phí vận chuyển.',
            'shipping_discount_code' => $rewards->payload($code),
        ]);
    }
}
