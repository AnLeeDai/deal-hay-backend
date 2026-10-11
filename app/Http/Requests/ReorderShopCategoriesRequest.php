<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderShopCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::SHOP_OWNER;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('product_categories', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_ids.required' => 'Vui lòng gửi thứ tự danh mục.',
            'category_ids.array' => 'Thứ tự danh mục không hợp lệ.',
            'category_ids.min' => 'Vui lòng gửi ít nhất một danh mục.',
            'category_ids.*.required' => 'Danh mục không hợp lệ.',
            'category_ids.*.string' => 'Mã danh mục phải là chuỗi ký tự.',
            'category_ids.*.exists' => 'Danh mục không tồn tại hoặc đã bị tắt.',
            'category_ids.*.distinct' => 'Danh sách danh mục bị trùng.',
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $submittedIds = collect($this->input('category_ids', []))->sort()->values();
                $activeIds = ProductCategory::query()->where('is_active', true)->pluck('id')->sort()->values();

                if ($submittedIds->all() !== $activeIds->all()) {
                    $validator->errors()->add(
                        'category_ids',
                        'Vui lòng sắp xếp đầy đủ danh sách danh mục hiện có.',
                    );
                }
            },
        ];
    }
}
