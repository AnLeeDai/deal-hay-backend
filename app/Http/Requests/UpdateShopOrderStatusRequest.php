<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = $this->route('shopOrder');

        return $this->user()?->role === UserRoleEnum::SHOP_OWNER
            && $order instanceof ShopOrder
            && $order->shop_owner_id === $this->user()?->getKey();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'confirmed', 'processing', 'shipping', 'completed', 'cancelled'])],
        ];
    }
}
