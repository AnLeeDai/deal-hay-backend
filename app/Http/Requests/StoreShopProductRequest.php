<?php

namespace App\Http\Requests;

use App\Enums\ImagePurposeEnum;
use App\Enums\UserRoleEnum;
use App\Http\Requests\Concerns\ValidatesShopProductCatalog;
use App\Models\User;
use App\Services\MediaClient;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopProductRequest extends FormRequest
{
    use ValidatesShopProductCatalog;

    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::SHOP_OWNER;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareShopProductCatalogForValidation();
    }

    protected function failedValidation(Validator $validator): void
    {
        $user = $this->user();
        if ($user instanceof User) {
            app(MediaClient::class)->discard($user, array_filter([
                $this->input('primary_image_upload'),
                ...((array) $this->input('gallery_image_uploads', [])),
            ]));
        }

        parent::failedValidation($validator);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => [
                'required',
                'string',
                'max:120',
                Rule::exists('product_categories', 'name')->where('is_active', true),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:1000', 'max:999999999'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'label' => ['nullable', 'string', 'max:80'],
            'image' => ['nullable', 'required_without:primary_image_upload', ...ImagePurposeEnum::Product->uploadedFileRules()],
            'primary_image_upload' => ['nullable', 'required_without:image', 'string', 'max:2048'],
            'gallery_images' => ['nullable', 'array', 'max:50'],
            'gallery_images.*' => ImagePurposeEnum::Product->uploadedFileRules(),
            'gallery_image_uploads' => ['nullable', 'array', 'max:50'],
            'gallery_image_uploads.*' => ['string', 'max:2048', 'distinct'],
            ...$this->shopProductCatalogRules(),
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [...$this->shopProductCatalogAfter(), function (Validator $validator): void {
            $galleryFiles = (array) $this->file('gallery_images', []);
            $galleryTokens = (array) $this->input('gallery_image_uploads', []);

            if (count($galleryFiles) + count($galleryTokens) > 50) {
                $validator->errors()->add('gallery_images', 'Mỗi sản phẩm được tải tối đa 50 ảnh bổ sung.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.string' => 'Tên sản phẩm phải là chuỗi ký tự.',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',
            'category.required' => 'Vui lòng chọn danh mục.',
            'category.string' => 'Danh mục phải là chuỗi ký tự.',
            'category.max' => 'Danh mục không được vượt quá 120 ký tự.',
            'category.exists' => 'Danh mục không tồn tại hoặc đã bị tắt.',
            'description.string' => 'Mô tả sản phẩm phải là chuỗi ký tự.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.integer' => 'Giá bán phải là số nguyên.',
            'price.min' => 'Giá bán tối thiểu là 1.000đ.',
            'price.max' => 'Giá bán quá lớn.',
            'stock.required' => 'Vui lòng nhập tồn kho.',
            'stock.integer' => 'Tồn kho phải là số nguyên.',
            'stock.min' => 'Tồn kho không được âm.',
            'stock.max' => 'Tồn kho quá lớn.',
            'label.string' => 'Nhãn sản phẩm phải là chuỗi ký tự.',
            'label.max' => 'Nhãn sản phẩm không được vượt quá 80 ký tự.',
            'image.required' => 'Vui lòng tải ảnh sản phẩm.',
            'image.file' => 'Ảnh sản phẩm phải là một tệp hợp lệ.',
            'image.image' => 'Tệp ảnh đại diện không phải là hình ảnh hợp lệ.',
            'image.mimetypes' => 'Ảnh đại diện phải là WebP tĩnh đã được xử lý trên thiết bị.',
            'image.max' => 'Ảnh đại diện không được vượt quá 512 KB.',
            'gallery_images.max' => 'Mỗi sản phẩm được tải tối đa 50 ảnh bổ sung.',
            'gallery_images.*.image' => 'Ảnh bổ sung không phải là hình ảnh hợp lệ.',
            'gallery_images.*.mimetypes' => 'Ảnh bổ sung phải là WebP tĩnh đã được xử lý trên thiết bị.',
            'gallery_images.*.max' => 'Mỗi ảnh bổ sung không được vượt quá 512 KB.',
            'gallery_image_uploads.max' => 'Mỗi sản phẩm được tải tối đa 50 ảnh bổ sung.',
            'variants.*.stock.required_with' => 'Vui lòng nhập tồn kho biến thể.',
        ];
    }
}
