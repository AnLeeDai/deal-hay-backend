<?php

namespace App\Http\Controllers;

use App\Actions\SetShopReaction;
use App\Http\Requests\InteractWithShopRequest;
use App\Http\Requests\UpdateShopReactionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ShopReactionController extends Controller
{
    public function __construct(private readonly SetShopReaction $setReaction) {}

    public function update(UpdateShopReactionRequest $request, User $shop): JsonResponse
    {
        $this->setReaction->handle($request->user(), $shop, $request->reaction());

        return response()->json([
            'message' => 'Cảm ơn bạn đã đánh giá shop.',
        ]);
    }

    public function destroy(InteractWithShopRequest $request, User $shop): JsonResponse
    {
        $this->setReaction->handle($request->user(), $shop, null);

        return response()->json([
            'message' => 'Đã xóa đánh giá của bạn.',
        ]);
    }
}
