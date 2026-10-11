<?php

namespace App\Http\Controllers;

use App\Actions\DeleteShopCoupon;
use App\Actions\SaveShopCoupon;
use App\Http\Requests\DeleteShopCouponRequest;
use App\Http\Requests\StoreShopCouponRequest;
use App\Http\Requests\UpdateShopCouponRequest;
use App\Models\ShopCoupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ShopCouponController extends Controller
{
    public function __construct(
        private readonly SaveShopCoupon $saveCoupon,
        private readonly DeleteShopCoupon $deleteCoupon,
    ) {}

    public function store(StoreShopCouponRequest $request): JsonResponse
    {
        $coupon = $this->saveCoupon->handle($request->user(), $request->validated());

        return response()->json([
            'message' => 'Tạo coupon thành công.',
            'data' => $coupon,
        ], 201);
    }

    public function update(UpdateShopCouponRequest $request, ShopCoupon $shopCoupon): JsonResponse
    {
        $coupon = $this->saveCoupon->handle($request->user(), $request->validated(), $shopCoupon);

        return response()->json([
            'message' => 'Cập nhật coupon thành công.',
            'data' => $coupon,
        ]);
    }

    public function destroy(DeleteShopCouponRequest $request, ShopCoupon $shopCoupon): JsonResponse
    {
        if (! $this->deleteCoupon->handle($request->user(), $shopCoupon)) {
            throw ValidationException::withMessages([
                'coupon' => 'Coupon đã phát sinh đơn hàng nên không thể xoá. Bạn có thể tắt coupon.',
            ]);
        }

        return response()->json([
            'message' => 'Xoá coupon thành công.',
        ]);
    }
}
