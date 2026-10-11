<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::ADMINISTRATOR
            && $this->route('productCategory') instanceof ProductCategory;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('product_categories', 'name')->ignore($this->route('productCategory'))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['name.required' => 'Vui lòng nhập tên danh mục.', 'name.unique' => 'Danh mục này đã tồn tại.', 'name.max' => 'Tên danh mục không được vượt quá 120 ký tự.'];
    }
}
