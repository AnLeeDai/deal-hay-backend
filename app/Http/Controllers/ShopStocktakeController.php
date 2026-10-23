<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopStocktakeRequest;
use App\Models\ShopStocktake;
use App\Services\SerializedStocktakeService;
use App\Services\ShopStocktakeService;
use Illuminate\Http\JsonResponse;

class ShopStocktakeController extends Controller
{
    public function __construct(private readonly ShopStocktakeService $stocktakes, private readonly SerializedStocktakeService $serialized) {}

    public function index(ShopStocktakeRequest $request): JsonResponse
    {
        return response()->json(['data' => ShopStocktake::query()->where('shop_owner_id', $request->user()->getKey())
            ->latest()->limit(30)->get(['id', 'name', 'status', 'counting_mode', 'stock_adjusted', 'created_at', 'saved_at', 'completed_at', 'confirmed_at', 'financial_summary'])]);
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
        $this->stocktakes->complete($stocktake, $request->user(), $request->boolean('adjust_stock'), $request->validated('confirmation_token'));

        return response()->json(['data' => $this->stocktakes->payload($stocktake->refresh()), 'message' => 'Đã chốt phiên kiểm kê.']);
    }

    public function save(ShopStocktakeRequest $request, ShopStocktake $stocktake): JsonResponse
    {
        $this->authorizeOwner($request, $stocktake);
        if ($stocktake->counting_mode === 'serialized') {
            $this->serialized->save($stocktake);
        } else {
            abort_unless($stocktake->status === 'counting', 422);
            $stocktake->update(['saved_at' => now()]);
        }

        return response()->json(['data' => $this->stocktakes->payload($stocktake->refresh()), 'message' => 'Đã lưu phiên kiểm kê.']);
    }

    public function undoUnit(ShopStocktakeRequest $request, ShopStocktake $stocktake, string $unit): JsonResponse
    {
        $this->authorizeOwner($request, $stocktake);
        abort_unless($stocktake->counting_mode === 'serialized', 422);
        $this->serialized->undo($stocktake, $unit);

        return response()->json(['data' => $this->stocktakes->payload($stocktake->refresh())]);
    }

    public function itemUnits(ShopStocktakeRequest $request, ShopStocktake $stocktake, string $item): JsonResponse
    {
        $this->authorizeOwner($request, $stocktake);
        $page = $stocktake->items()->findOrFail($item)->units()->orderBy('sku')->paginate((int) $request->validated('per_page', 100));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($entry): array => [
                'id' => $entry->getKey(), 'inventory_unit_id' => $entry->inventory_unit_id,
                'sku' => $entry->sku, 'code' => $entry->code, 'expected' => $entry->expected, 'counted' => $entry->counted,
            ])->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
        ]);
    }

    private function authorizeOwner(ShopStocktakeRequest $request, ShopStocktake $stocktake): void
    {
        abort_unless($stocktake->shop_owner_id === $request->user()->getKey(), 404);
    }
}
