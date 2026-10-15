<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListProductDiscountCodesRequest;
use App\Http\Requests\SaveProductDiscountCodeRequest;
use App\Models\ProductDiscountCode;
use App\Services\ProductDiscountCodeService;
use Illuminate\Http\JsonResponse;

class ProductDiscountCodeController extends Controller
{
    public function __construct(private readonly ProductDiscountCodeService $codes) {}

    public function index(ListProductDiscountCodesRequest $request): JsonResponse
    {
        $codes = ProductDiscountCode::query()->where('user_id', $request->user()->getKey())
            ->when($request->filled('product_id'), fn ($query) => $query->where('shop_product_id', $request->validated('product_id')))
            ->with('product.shopOwner')->latest()->get();

        return response()->json(['discount_codes' => $codes->map(fn (ProductDiscountCode $code): array => $this->codes->payload($code))->all()]);
    }

    public function save(SaveProductDiscountCodeRequest $request, ProductDiscountCode $productDiscountCode): JsonResponse
    {
        $code = $this->codes->save($request->user(), $productDiscountCode);

        return response()->json(['message' => 'Đã lưu mã giảm giá vào ví của bạn.', 'discount_code' => $this->codes->payload($code)]);
    }
}
