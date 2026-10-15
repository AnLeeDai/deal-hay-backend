<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Models\ProductAdView;
use App\Models\ProductDiscountCode;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\ProductDiscountCodeService;
use App\Services\RewardedAdvertisementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAdvertisementController extends Controller
{
    public function store(Request $request, User $shop, ShopProduct $shopProduct, RewardedAdvertisementService $advertisements): JsonResponse
    {
        $this->assertAvailable($request, $shop, $shopProduct);
        $session = $advertisements->start($request->user(), $shopProduct);
        $view = $session['view'];

        return response()->json(['id' => $view->getKey(), 'provider' => $view->provider, 'ad_type' => $view->ad_type, 'presentation' => $session['presentation']], 201);
    }

    public function complete(Request $request, User $shop, ShopProduct $shopProduct, ProductAdView $productAdView, ProductDiscountCodeService $codes, RewardedAdvertisementService $advertisements): JsonResponse
    {
        $discountCode = DB::transaction(function () use ($request, $shop, $shopProduct, $productAdView, $advertisements): ProductDiscountCode {
            $shop = User::query()->lockForUpdate()->findOrFail($shop->getKey());
            $product = ShopProduct::query()->where('shop_owner_id', $shop->getKey())->lockForUpdate()->findOrFail($shopProduct->getKey());
            $this->assertAvailable($request, $shop, $product);
            $view = ProductAdView::query()->lockForUpdate()->findOrFail($productAdView->getKey());
            abort_unless($view->user_id === $request->user()->getKey()
                && $view->shop_product_id === $product->getKey(), 404);

            return $advertisements->complete($request->user(), $view);
        }, attempts: 5);

        return response()->json([
            'message' => 'Đã nhận mã giảm giá cho sản phẩm. Bạn có thể dùng ngay hoặc lưu dùng sau.',
            'id' => $productAdView->getKey(),
            'discount_code' => $codes->payload($discountCode),
        ]);
    }

    private function assertAvailable(Request $request, User $shop, ShopProduct $product): void
    {
        abort_unless($shop->role === UserRoleEnum::SHOP_OWNER && $shop->is_active && $product->is_active
            && $product->shop_owner_id === $shop->getKey(), 404);
        abort_if($shop->getKey() === $request->user()->getKey(), 403);
    }
}
