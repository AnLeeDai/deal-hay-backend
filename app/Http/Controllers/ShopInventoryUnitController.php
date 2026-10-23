<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopInventoryUnitRequest;
use App\Models\ShopInventoryUnit;
use App\Services\ShopInventoryUnitService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ShopInventoryUnitController extends Controller
{
    public function __construct(private readonly ShopInventoryUnitService $units) {}

    public function index(ShopInventoryUnitRequest $request): JsonResponse
    {
        $this->units->ensureForOwner($request->user());
        $query = ShopInventoryUnit::query()->where('shop_owner_id', $request->user()->getKey())
            ->where('status', 'available')->whereHas('product')
            ->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereNull('shop_product_variant_id')->whereHas('product', fn (Builder $query) => $query->where('has_variants', false));
                })->orWhereHas('variant', fn (Builder $query) => $query->whereNull('retired_at'));
            })
            ->with(['product', 'variant.optionValues.option'])->orderBy('id');
        if ($request->filled('product_id')) {
            $query->where('shop_product_id', $request->validated('product_id'));
        }
        $search = trim((string) $request->validated('search', ''));
        if ($search !== '') {
            $unitId = Str::startsWith(Str::upper($search), 'DH:U:') ? Str::lower(substr($search, 5)) : $search;
            $query->where(function (Builder $query) use ($search, $unitId): void {
                $query->where('sku', 'like', '%'.$search.'%')->orWhere('id', $unitId)
                    ->orWhereHas('product', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%'));
            });
        }
        $page = $query->paginate((int) $request->validated('per_page', 100));

        return response()->json([
            'data' => $page->getCollection()->map(function (ShopInventoryUnit $unit): array {
                $product = $unit->product;
                $variant = $unit->variant;

                return [
                    'id' => $unit->getKey(), 'code' => $unit->code, 'sku' => $unit->sku,
                    'shop_product_id' => $unit->shop_product_id, 'shop_product_variant_id' => $unit->shop_product_variant_id,
                    'product_name' => $product->name, 'variant_name' => $variant?->displayName(),
                    'stock' => 1, 'is_active' => (bool) $product->is_active && ($variant === null || (bool) $variant->is_active),
                    'price' => (int) ($variant?->price ?? $product->price), 'status' => $unit->status,
                ];
            })->values()->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
        ]);
    }
}
