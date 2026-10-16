<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartRewardedTaskRequest;
use App\Models\RewardedTaskSession;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\RewardedTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardedTaskController extends Controller
{
    public function __construct(private readonly RewardedTaskService $tasks) {}

    public function configuration(User $shop, ShopProduct $shopProduct): JsonResponse
    {
        $this->tasks->ensureProductIsPublic($shop, $shopProduct);

        return response()->json($this->tasks->configuration());
    }

    public function store(StartRewardedTaskRequest $request, User $shop, ShopProduct $shopProduct): JsonResponse
    {
        $this->tasks->ensureProductIsPublic($shop, $shopProduct);
        $session = $this->tasks->start($request->user(), $shopProduct);

        return response()->json([
            'message' => 'Hoàn thành nhiệm vụ và chờ nhà cung cấp xác nhận để nhận mã giảm giá.',
            'task' => $this->tasks->payload($session),
        ], 201);
    }

    public function show(Request $request, User $shop, ShopProduct $shopProduct, RewardedTaskSession $rewardedTaskSession): JsonResponse
    {
        $this->tasks->ensureProductIsPublic($shop, $shopProduct);
        abort_unless($rewardedTaskSession->user_id === $request->user()->getKey()
            && $rewardedTaskSession->shop_product_id === $shopProduct->getKey(), 404);

        return response()->json(['task' => $this->tasks->payload($rewardedTaskSession)]);
    }
}
