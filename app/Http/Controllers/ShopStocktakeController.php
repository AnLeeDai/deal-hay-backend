<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopStocktakeRequest;
use App\Models\ShopStocktake;
use App\Services\ShopStocktakeService;
use Illuminate\Http\JsonResponse;

class ShopStocktakeController extends Controller
{
    public function __construct(private readonly ShopStocktakeService $stocktakes) {}

    public function index(ShopStocktakeRequest $request): JsonResponse
    {
        return response()->json(['data' => ShopStocktake::query()->where('shop_owner_id', $request->user()->getKey())
            ->latest()->limit(30)->get(['id', 'name', 'status', 'stock_adjusted', 'created_at', 'completed_at'])]);
    }

    public function store(ShopStocktakeRequest $request): JsonResponse
    {
        $stocktake = $this->stocktakes->create($request->user(), $request->validated('name'));

        return response()->json(['data' => $this->stocktakes->payload($stocktake->refresh())], 201);
    }

    public function show(ShopStocktakeRequest $request, ShopStocktake $stocktake): JsonResponse
    {
        $this->authorizeOwner($request, $stocktake);

        return response()->json(['data' => $this->stocktakes->payload($stocktake)]);
    }

    public function scan(ShopStocktakeRequest $request, ShopStocktake $stocktake): JsonResponse
    {
        $this->authorizeOwner($request, $stocktake);
        $this->stocktakes->scan($stocktake, $request->validated('code'), (int) $request->validated('quantity', 1), $request->validated('scan_id'));

        return response()->json(['data' => $this->stocktakes->payload($stocktake->refresh())]);
    }

    public function updateItem(ShopStocktakeRequest $request, ShopStocktake $stocktake, string $item): JsonResponse
    {
        $this->authorizeOwner($request, $stocktake);
        $this->stocktakes->updateItem($stocktake, $item, (int) $request->validated('counted_quantity'));

        return response()->json(['data' => $this->stocktakes->payload($stocktake->refresh())]);
    }

    public function complete(ShopStocktakeRequest $request, ShopStocktake $stocktake): JsonResponse
    {
        $this->authorizeOwner($request, $stocktake);
        $this->stocktakes->complete($stocktake, $request->user(), $request->boolean('adjust_stock'));

        return response()->json(['data' => $this->stocktakes->payload($stocktake->refresh()), 'message' => 'Đã chốt phiên kiểm kê.']);
    }

    private function authorizeOwner(ShopStocktakeRequest $request, ShopStocktake $stocktake): void
    {
        abort_unless($stocktake->shop_owner_id === $request->user()->getKey(), 404);
    }
}
