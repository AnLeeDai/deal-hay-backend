<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Http\Requests\UpdateShopShippingSettingRequest;
use App\Models\ShopShippingSetting;
use App\Models\User;
use App\Services\ShopShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopShippingSettingController extends Controller
{
    public function show(Request $request, ShopShippingService $shipping): JsonResponse
    {
        abort_unless($request->user()?->role === UserRoleEnum::SHOP_OWNER && $request->user()?->is_active, 403);

        return response()->json(['shipping_settings' => $shipping->settings($request->user())]);
    }

    public function update(UpdateShopShippingSettingRequest $request, ShopShippingService $shipping): JsonResponse
    {
        $data = $request->validated();
        $rates = collect($data['ward_rates'])->map(fn (array $rate): array => [
            'ward_code' => (int) $rate['ward_code'],
            'fee' => (int) $rate['fee'],
        ])->sortBy('ward_code')->values()->all();

        DB::transaction(function () use ($request, $data, $rates): void {
            $shop = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            abort_unless($shop->role === UserRoleEnum::SHOP_OWNER && $shop->is_active, 403);
            ShopShippingSetting::query()->updateOrCreate(['shop_owner_id' => $shop->getKey()], [
                'enabled' => (bool) $data['enabled'],
                'base_fee' => (int) $data['base_fee'],
                'ward_rates' => $rates,
            ]);
        }, attempts: 5);

        return response()->json([
            'message' => 'Đã lưu phí vận chuyển Hà Nội.',
            'shipping_settings' => $shipping->settings($request->user()),
        ]);
    }
}
