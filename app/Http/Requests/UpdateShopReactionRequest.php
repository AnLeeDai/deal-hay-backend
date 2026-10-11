<?php

namespace App\Http\Requests;

use App\Enums\ShopReactionTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateShopReactionRequest extends InteractWithShopRequest
{
    public function reaction(): ShopReactionTypeEnum
    {
        return ShopReactionTypeEnum::from((string) $this->validated('reaction'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reaction' => ['required', Rule::enum(ShopReactionTypeEnum::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reaction.required' => 'Vui lòng chọn thích hoặc không thích.',
            'reaction.enum' => 'Lựa chọn đánh giá không hợp lệ.',
        ];
    }
}
