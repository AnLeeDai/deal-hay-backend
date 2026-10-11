<?php

namespace App\Http\Controllers;

use App\Actions\DeleteShopProductReview;
use App\Actions\ReplyToShopProductReview;
use App\Actions\SaveShopProductReview;
use App\Http\Requests\InteractWithShopProductReviewRequest;
use App\Http\Requests\ReplyShopProductReviewRequest;
use App\Http\Requests\UpdateShopProductReviewRequest;
use App\Models\ShopProduct;
use App\Models\ShopProductReview;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ShopProductReviewController extends Controller
{
    public function __construct(
        private readonly SaveShopProductReview $saveReview,
        private readonly DeleteShopProductReview $deleteReview,
        private readonly ReplyToShopProductReview $replyToReview,
    ) {}

    public function update(UpdateShopProductReviewRequest $request, User $shop, ShopProduct $shopProduct): JsonResponse
    {
        $this->saveReview->handle($request->user(), $shopProduct, $request->validated(), $request->file('image'));

        return response()->json([
            'message' => 'Đánh giá sản phẩm đã được lưu.',
        ]);
    }

    public function destroy(InteractWithShopProductReviewRequest $request, User $shop, ShopProduct $shopProduct): JsonResponse
    {
        $this->deleteReview->handle($request->user(), $shopProduct);

        return response()->json([
            'message' => 'Đã xóa đánh giá sản phẩm.',
        ]);
    }

    public function reply(ReplyShopProductReviewRequest $request, ShopProductReview $shopProductReview): JsonResponse
    {
        $this->replyToReview->handle($request->user(), $shopProductReview, $request->reply());

        return response()->json([
            'message' => 'Đã phản hồi đánh giá của khách hàng.',
            'data' => $shopProductReview->refresh(),
        ]);
    }
}
