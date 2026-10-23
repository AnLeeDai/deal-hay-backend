<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopStocktake;
use Illuminate\Foundation\Http\FormRequest;

class ShopStocktakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->role !== UserRoleEnum::SHOP_OWNER || ! $this->user()?->is_active) {
            return false;
        }
        $stocktake = $this->route('stocktake');
        if ($stocktake instanceof ShopStocktake) {
            abort_unless($stocktake->shop_owner_id === $this->user()->getKey(), 404);
        }

        return true;
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'store' => ['name' => ['required', 'string', 'max:120']],
            'scan' => [
                'code' => ['required', 'string', 'max:255'],
                'quantity' => ['sometimes', 'integer', 'min:1', 'max:999999'],
                'scan_id' => ['required', 'uuid'],
            ],
            'updateItem' => ['counted_quantity' => ['required', 'integer', 'min:0', 'max:999999']],
            'undoUnit' => ['counted' => ['required', 'boolean', 'declined']],
            'itemUnits' => ['page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']],
            'complete' => array_merge(['adjust_stock' => ['required', 'boolean']], $this->route('stocktake')?->counting_mode === 'serialized' ? [
                'confirmed' => ['required', 'accepted'],
                'confirmation_token' => ['required', 'string', 'size:64'],
            ] : []),
            default => [],
        };
    }
}
