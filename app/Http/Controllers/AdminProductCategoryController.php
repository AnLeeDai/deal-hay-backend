<?php

namespace App\Http\Controllers;

use App\Actions\DeleteProductCategory;
use App\Actions\GetAdminProductCategories;
use App\Actions\SaveProductCategory;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AdminProductCategoryController extends Controller
{
    public function __construct(
        private readonly GetAdminProductCategories $categories,
        private readonly SaveProductCategory $saveCategory,
        private readonly DeleteProductCategory $deleteCategory,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'categories' => $this->categories->handle(),
        ]);
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $category = $this->saveCategory->handle($request->validated());

        return response()->json([
            'message' => 'Thêm danh mục thành công.',
            'data' => $category,
        ], 201);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $category = $this->saveCategory->handle($request->validated(), $productCategory);

        return response()->json([
            'message' => 'Cập nhật danh mục thành công.',
            'data' => $category,
        ]);
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        if (! $this->deleteCategory->handle($productCategory)) {
            throw ValidationException::withMessages([
                'category' => 'Không thể xoá danh mục đang có sản phẩm.',
            ]);
        }

        return response()->json([
            'message' => 'Xoá danh mục thành công.',
        ]);
    }
}
