<?php

namespace App\Http\Controllers;

use App\Actions\CreateShopOrder;
use App\Actions\UpdateShopOrderStatus;
use App\Http\Requests\StoreShopOrderRequest;
use App\Http\Requests\UpdateShopOrderStatusRequest;
use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ShopOrderController extends Controller
{
    public function __construct(
        private readonly CreateShopOrder $createOrder,
        private readonly UpdateShopOrderStatus $updateOrderStatus,
    ) {}

    public function store(StoreShopOrderRequest $request): JsonResponse
    {
        /** @var User $shopOwner */
        $shopOwner = $request->user();
        $replayed = $this->createOrder->handle($shopOwner, $request->validated());

        return response()->json([
            'message' => $replayed
                ? 'Đơn hàng đã được tạo trước đó; hệ thống không giữ kho lần thứ hai.'
                : 'Tạo đơn hàng và giữ tồn kho thành công.',
            'replayed' => $replayed,
        ], $replayed ? 200 : 201)->header('Idempotency-Replayed', $replayed ? 'true' : 'false');
    }

    public function updateStatus(UpdateShopOrderStatusRequest $request, ShopOrder $shopOrder): JsonResponse
    {
        /** @var User $shopOwner */
        $shopOwner = $request->user();
        $this->updateOrderStatus->handle($shopOwner, $shopOrder, (string) $request->validated('status'));

        return response()->json([
            'message' => 'Cập nhật trạng thái đơn hàng thành công.',
            'data' => $shopOrder->refresh(),
        ]);
    }
}
