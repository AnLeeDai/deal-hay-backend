<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Models\ProductAdView;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductAdvertisementController extends Controller
{
    public function store(Request $request, User $shop, ShopProduct $shopProduct): JsonResponse
    {
        $this->assertAvailable($request, $shop, $shopProduct);
        $view = ProductAdView::query()->create([
            'user_id' => $request->user()->getKey(),
            'shop_product_id' => $shopProduct->getKey(),
            'video_url' => $shopProduct->advertisement_video_url,
            'duration_seconds' => $shopProduct->advertisement_duration_seconds,
            'expires_at' => now()->addHour(),
        ]);

        return response()->json(['id' => $view->getKey(), 'video_url' => $view->video_url, 'duration_seconds' => $view->duration_seconds], 201);
    }

    public function complete(Request $request, User $shop, ShopProduct $shopProduct, ProductAdView $productAdView): JsonResponse
    {
        $this->assertAvailable($request, $shop, $shopProduct);
        abort_unless($productAdView->user_id === $request->user()->getKey()
            && $productAdView->shop_product_id === $shopProduct->getKey(), 404);

        if ($productAdView->expires_at->isPast() || $productAdView->shop_order_id !== null
            || $productAdView->video_url !== $shopProduct->advertisement_video_url
            || $productAdView->duration_seconds !== (int) $shopProduct->advertisement_duration_seconds
            || $productAdView->created_at->addSeconds($productAdView->duration_seconds)->isFuture()) {
            throw ValidationException::withMessages(['advertisement_view_id' => 'Bạn cần xem hết quảng cáo hợp lệ trước khi nhận ưu đãi.']);
        }

        $productAdView->update(['completed_at' => $productAdView->completed_at ?? now()]);

        return response()->json(['message' => 'Đã mở ưu đãi cho một đơn hàng.', 'id' => $productAdView->getKey()]);
    }

    private function assertAvailable(Request $request, User $shop, ShopProduct $product): void
    {
        abort_unless($shop->role === UserRoleEnum::SHOP_OWNER && $shop->is_active && $product->is_active
            && $product->shop_owner_id === $shop->getKey(), 404);
        abort_if($shop->getKey() === $request->user()->getKey(), 403);
        abort_unless($product->hasAdvertisement(), 422, 'Sản phẩm chưa có quảng cáo hoặc ưu đãi khả dụng.');
    }
}
