<?php

namespace App\Http\Requests;

use App\Models\ShippingRewardView;
use App\Models\ShopProduct;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShippingOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('shopProduct');
        $view = $this->route('shippingRewardView');
        if ($view instanceof ShippingRewardView) {
            abort_unless($view->user_id === $this->user()?->getKey(), 404);
        }

        return $this->user()?->is_active && $product instanceof ShopProduct
            && $product->shop_owner_id !== $this->user()?->getKey();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
