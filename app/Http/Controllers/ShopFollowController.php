<?php

namespace App\Http\Controllers;

use App\Actions\SetShopFollow;
use App\Http\Requests\InteractWithShopRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ShopFollowController extends Controller
{
    public function __construct(private readonly SetShopFollow $setFollow) {}

    public function store(InteractWithShopRequest $request, User $shop): JsonResponse
    {
        $this->setFollow->handle($request->user(), $shop, true);

        return response()->json([
            'message' => 'Bạn đang theo dõi shop này.',
        ], 201);
    }

    public function destroy(InteractWithShopRequest $request, User $shop): JsonResponse
    {
        $this->setFollow->handle($request->user(), $shop, false);

        return response()->json([
            'message' => 'Đã bỏ theo dõi shop.',
        ]);
    }
}
