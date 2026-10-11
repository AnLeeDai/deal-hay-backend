<?php

namespace App\Http\Controllers;

use App\Actions\DeleteShopProductByAdmin;
use App\Actions\GetAdminShopPage;
use App\Http\Requests\AdminDeleteShopProductRequest;
use App\Http\Requests\ListAdminShopsRequest;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminShopController extends Controller
{
    public function __construct(
        private readonly GetAdminShopPage $shopPage,
        private readonly DeleteShopProductByAdmin $deleteProduct,
    ) {}

    public function index(ListAdminShopsRequest $request): JsonResponse
    {
        return response()->json($this->shopPage->handle($request->validated()));
    }

    public function show(ListAdminShopsRequest $request, User $shop): JsonResponse
    {
        return response()->json($this->shopPage->handle($request->validated(), $shop));
    }

    public function destroyProduct(AdminDeleteShopProductRequest $request, User $shop, ShopProduct $shopProduct): JsonResponse
    {
        $this->deleteProduct->handle($shop, $shopProduct, (string) $request->validated('reason'));

        return response()->json([
            'message' => 'Đã xoá sản phẩm và gửi thông báo cho shop.',
        ]);
    }
}
