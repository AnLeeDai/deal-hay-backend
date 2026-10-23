<?php

namespace App\Http\Requests;

use App\Enums\ImagePurposeEnum;
use App\Enums\ShopReactionTypeEnum;
use App\Services\ShopProductReviewEligibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateShopProductReviewRequest extends InteractWithShopProductReviewRequest
{
    public function authorize(): bool
    {
        return parent::authorize()
            && app(ShopProductReviewEligibility::class)->canReview($this->user(), $this->route('shopProduct'));
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
            'content' => ['required', 'string', 'min:5', 'max:2000'],
            'image' => ['sometimes', 'nullable', ...ImagePurposeEnum::Review->uploadedFileRules()],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reaction.required' => 'Vui lòng chọn thích hoặc không thích.',
            'reaction.enum' => 'Lựa chọn đánh giá không hợp lệ.',
            'content.required' => 'Vui lòng nhập nội dung đánh giá.',
            'content.string' => 'Nội dung đánh giá không hợp lệ.',
            'content.min' => 'Nội dung đánh giá phải có ít nhất 5 ký tự.',
            'content.max' => 'Nội dung đánh giá không được vượt quá 2.000 ký tự.',
            'image.file' => 'Ảnh đánh giá phải là một tệp hợp lệ.',
        ];
    }
}
