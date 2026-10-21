<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Http\Requests\ShippingQuoteRequest;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\ShopShippingService;
use Illuminate\Http\JsonResponse;

class ShippingQuoteController extends Controller
{
    public function __invoke(ShippingQuoteRequest $request, User $shop, ShopProduct $shopProduct, ShopShippingService $shipping): JsonResponse
    {
        abort_unless($shop->role === UserRoleEnum::SHOP_OWNER && $shop->is_active
            && $shopProduct->is_active && $shopProduct->shop_owner_id === $shop->getKey(), 404);

        return response()->json([
            'shipping_quote' => $shipping->quote($shop, (int) $request->validated('shipping_province_code'), (int) $request->validated('shipping_ward_code')),
        ]);
    }
}
