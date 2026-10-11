<?php

namespace App\Http\Controllers;

use App\Actions\DeleteShopProduct;
use App\Actions\GetShopManagementData;
use App\Actions\ReorderShopCategories;
use App\Actions\SaveShopProductDetails;
use App\Actions\UpdateShopProductStatus;
use App\Actions\UpdateShopProfile;
use App\Enums\UserRoleEnum;
use App\Http\Requests\ReorderShopCategoriesRequest;
use App\Http\Requests\StoreShopProductRequest;
use App\Http\Requests\UpdateShopProductRequest;
use App\Http\Requests\UpdateShopProductStatusRequest;
use App\Http\Requests\UpdateShopRequest;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function __construct(
        private readonly GetShopManagementData $managementData,
        private readonly UpdateShopProfile $updateProfile,
        private readonly SaveShopProductDetails $saveProduct,
        private readonly DeleteShopProduct $deleteProduct,
        private readonly ReorderShopCategories $reorderCategories,
        private readonly UpdateShopProductStatus $updateProductStatus,
    ) {}

    public function manage(): JsonResponse
    {
        return response()->json($this->managementData->handle($this->shopOwner()));
    }

    public function update(UpdateShopRequest $request): JsonResponse
    {
        $shopOwner = $this->shopOwner();
        $this->updateProfile->handle($shopOwner, $request->validated());

        return response()->json([
            'message' => 'Cập nhật thông tin cửa hàng thành công.',
            'data' => $shopOwner->refresh(),
        ]);
    }

    public function storeProduct(StoreShopProductRequest $request): JsonResponse
    {
        $product = $this->saveProduct->create(
            $this->shopOwner(),
            $request->validated(),
            $request->file('image'),
            $request->file('gallery_images', []),
            $request->validated('primary_image_upload'),
            $request->validated('gallery_image_uploads', []),
        );

        return response()->json([
            'message' => 'Thêm sản phẩm thành công.',
            'data' => $product,
        ], 201);
    }

    public function updateProduct(UpdateShopProductRequest $request, ShopProduct $shopProduct): JsonResponse
    {
        $product = $this->saveProduct->update(
            $this->shopOwner(),
            $shopProduct,
            $request->validated(),
            $request->file('image'),
            $request->file('gallery_images', []),
            $request->validated('remove_image_ids', []),
            $request->validated('primary_image_upload'),
            $request->validated('gallery_image_uploads', []),
        );

        return response()->json([
            'message' => 'Cập nhật sản phẩm thành công.',
            'data' => $product,
        ]);
    }

    public function destroyProduct(ShopProduct $shopProduct): JsonResponse
    {
        if (! $this->deleteProduct->handle($this->shopOwner(), $shopProduct)) {
            throw ValidationException::withMessages([
                'product' => 'Không thể xoá sản phẩm đã phát sinh đơn hàng. Bạn có thể chuyển sản phẩm sang trạng thái ẩn.',
            ]);
        }

        return response()->json([
            'message' => 'Xoá sản phẩm thành công.',
        ]);
    }

    public function updateProductStatus(UpdateShopProductStatusRequest $request, ShopProduct $shopProduct): JsonResponse
    {
        $this->updateProductStatus->handle($this->shopOwner(), $shopProduct, $request->boolean('is_active'));

        return response()->json([
            'message' => $shopProduct->refresh()->is_active ? 'Đã mở bán sản phẩm.' : 'Đã ẩn sản phẩm.',
            'data' => $shopProduct->refresh(),
        ]);
    }

    public function reorderCategories(ReorderShopCategoriesRequest $request): JsonResponse
    {
        $this->reorderCategories->handle($this->shopOwner(), $request->validated('category_ids'));

        return response()->json([
            'message' => 'Cập nhật thứ tự danh mục thành công.',
        ]);
    }

    private function shopOwner(): User
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role !== UserRoleEnum::SHOP_OWNER) {
            abort(403, 'Bạn không có quyền truy cập trang quản lý shop.');
        }

        return $user;
    }
}
