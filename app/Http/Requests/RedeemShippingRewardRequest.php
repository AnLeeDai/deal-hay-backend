<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class RedeemShippingRewardRequest extends ShippingOfferRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['rewarded_slot_granted' => ['bail', 'required', 'boolean:strict', 'accepted']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['rewarded_slot_granted.*' => 'Quảng cáo chưa cấp phần thưởng. Vui lòng hoàn thành quảng cáo để nhận mã miễn phí vận chuyển.'];
    }
}
