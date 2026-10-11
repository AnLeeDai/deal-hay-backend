<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopProductReview;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReplyShopProductReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $review = $this->route('shopProductReview');

        return $user !== null
            && $user->role === UserRoleEnum::SHOP_OWNER
            && (bool) $user->is_active
            && $review instanceof ShopProductReview
            && $review->product()->where('shop_owner_id', $user->getKey())->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }

    public function reply(): string
    {
        return (string) $this->validated('reply');
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reply.required' => 'Vui lòng nhập nội dung phản hồi.',
            'reply.string' => 'Nội dung phản hồi không hợp lệ.',
            'reply.min' => 'Nội dung phản hồi phải có ít nhất 2 ký tự.',
            'reply.max' => 'Nội dung phản hồi không được vượt quá 2.000 ký tự.',
        ];
    }
}
