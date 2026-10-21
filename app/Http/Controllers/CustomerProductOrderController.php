<?php

namespace App\Http\Controllers;

use App\Actions\CreateCustomerProductOrder;
use App\Http\Requests\StoreCustomerProductOrderRequest;
use App\Models\ProductDiscountCode;
use App\Models\ShippingDiscountCode;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CustomerProductOrderController extends Controller
{
    public function store(StoreCustomerProductOrderRequest $request, User $shop, ShopProduct $shopProduct, CreateCustomerProductOrder $createOrder): JsonResponse
    {
        $order = $createOrder->handle($request->user(), $shop, $shopProduct, $request->validated());

        return response()->json([
            'message' => 'Đặt hàng thành công.',
            'order' => [
                'id' => $order->getKey(),
                'order_number' => $order->order_number,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'shipping_fee' => $order->shipping_fee,
                'shipping_discount_amount' => $order->shipping_discount_amount,
                'shipping_total' => $order->shipping_total,
                'shipping_province_code' => $order->shipping_province_code,
                'shipping_ward_code' => $order->shipping_ward_code,
                'total' => $order->total,
                'redeemed_discount_codes' => ProductDiscountCode::query()->where('shop_order_id', $order->getKey())->pluck('code')->all(),
                'redeemed_shipping_discount_code' => ShippingDiscountCode::query()->where('shop_order_id', $order->getKey())->value('code'),
            ],
        ], 201);
    }
}
