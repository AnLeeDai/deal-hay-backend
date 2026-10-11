<?php

namespace App\Http\Controllers;

use App\Actions\AdjustShopInventory;
use App\Http\Requests\AdjustShopInventoryRequest;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;

class ShopInventoryController extends Controller
{
    public function __construct(private readonly AdjustShopInventory $adjustInventory) {}

    public function adjust(AdjustShopInventoryRequest $request, ShopProduct $shopProduct): JsonResponse
    {
        $this->adjustInventory->handle(
            $request->user(),
            $shopProduct,
            $request->variantId(),
            $request->quantityDelta(),
            $request->note(),
        );

        return response()->json([
            'message' => 'Điều chỉnh tồn kho thành công.',
            'data' => $shopProduct->refresh(),
        ]);
    }
}
