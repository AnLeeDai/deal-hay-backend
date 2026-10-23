<?php

namespace App\Http\Requests;

use App\Enums\ImagePurposeEnum;
use App\Enums\UserRoleEnum;
use App\Services\ImageService;
use Illuminate\Foundation\Http\FormRequest;

class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->route()->getActionMethod() !== 'updateShopAvatar'
                || $this->user()->role === UserRoleEnum::SHOP_OWNER);
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'storeStaged', 'storeUploadSessions' => [
                'uploads' => ['required', 'array', 'list', 'max:51'],
                'uploads.*' => ['required', 'array:size,content_type'],
                'uploads.*.size' => ['required', 'integer', 'min:1', 'max:'.ImageService::MAX_IMAGE_BYTES],
                'uploads.*.content_type' => ['required', 'string', 'in:image/webp'],
            ],
            'prepareStaged' => [
                'token' => ['required', 'string', 'max:2048'],
                'image' => ['required', ...ImagePurposeEnum::Product->uploadedFileRules()],
            ],
            'discardStaged' => [
                'tokens' => ['required', 'array', 'list', 'max:51'],
                'tokens.*' => ['required', 'string', 'max:2048', 'distinct'],
            ],
            default => ['avatar' => ['required', ...ImagePurposeEnum::Avatar->uploadedFileRules()]],
        };
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'uploads.max' => 'Mỗi lần được tải tối đa 51 ảnh.',
            'uploads.*.size.min' => 'Ảnh tải lên phải có ít nhất 1 byte.',
            'uploads.*.size.max' => 'Ảnh đã xử lý không được vượt quá 512 KB.',
            'uploads.*.content_type.in' => 'Ảnh tải lên phải là WebP tĩnh đã được xử lý trên thiết bị.',
            'size.min' => 'Ảnh tải lên phải có ít nhất 1 byte.',
            'size.max' => 'Ảnh đã xử lý không được vượt quá 20 KB.',
            'image.image' => 'Tệp tải lên phải là hình ảnh hợp lệ.',
            'image.max' => 'Ảnh đã xử lý không được vượt quá 512 KB.',
            'image.mimetypes' => 'Vui lòng gửi ảnh WebP tĩnh đã được xử lý trên thiết bị.',
            'avatar.image' => 'Ảnh đại diện phải là hình ảnh hợp lệ.',
            'avatar.max' => 'Ảnh đại diện đã xử lý không được vượt quá 20 KB.',
            'avatar.mimetypes' => 'Vui lòng gửi ảnh WebP tĩnh đã được xử lý trên thiết bị.',
        ];
    }
}
