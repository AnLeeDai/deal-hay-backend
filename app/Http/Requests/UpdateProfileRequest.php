<?php

namespace App\Http\Requests;

use App\Rules\ValidProvince;
use App\Rules\ValidWardInProvince;
use App\Services\AddressService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower($this->string('email')->squish()->toString()),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(fn ($query) => $query->where('role', $user?->role))
                    ->ignore($user?->getKey()),
            ],
            'phone_number' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^(\+84|0)[3-9]\d{8}$/',
                Rule::unique('users', 'phone_number')
                    ->where(fn ($query) => $query->where('role', $user?->role))
                    ->ignore($user?->getKey()),
            ],
            'province' => ['sometimes', 'nullable', 'string', 'max:255', new ValidProvince],
            'ward' => ['sometimes', 'nullable', 'string', 'max:255', new ValidWardInProvince($this->input('province', $user?->province))],
            'address_detail' => ['sometimes', 'string', 'max:255'],
            'email_change_code' => ['sometimes', 'nullable', 'digits:6'],
            'avatar' => ['sometimes', 'file'],
        ];
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasAny(['province', 'ward', 'address_detail'])) {
                return;
            }

            $province = $this->input('province', $this->user()?->province);
            $ward = $this->input('ward', $this->user()?->ward);
            $detail = $this->input('address_detail', $this->user()?->address_detail);

            if (blank($province) && blank($ward) && blank($detail)) {
                return;
            }

            $addresses = app(AddressService::class);

            if (! $validator->errors()->has('province') && $addresses->provinceCode($province) === null) {
                $validator->errors()->add('province', 'Vui lòng chọn tỉnh/thành từ danh sách.');
            }

            if (! $validator->errors()->has('ward') && ! $addresses->wardBelongsToProvince($ward, $province)) {
                $validator->errors()->add('ward', 'Vui lòng chọn phường/xã thuộc tỉnh/thành đã chọn.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'full_name.string' => 'Họ và tên phải là chuỗi ký tự.',
            'full_name.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'email.string' => 'Email phải là chuỗi ký tự.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email này đã được sử dụng cho loại tài khoản này.',
            'phone_number.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone_number.regex' => 'Số điện thoại không hợp lệ.',
            'phone_number.unique' => 'Số điện thoại này đã được sử dụng.',
            'province.string' => 'Tỉnh hoặc thành phố phải là chuỗi ký tự.',
            'province.max' => 'Tỉnh hoặc thành phố không được vượt quá 255 ký tự.',
            'ward.string' => 'Phường hoặc xã phải là chuỗi ký tự.',
            'ward.max' => 'Phường hoặc xã không được vượt quá 255 ký tự.',
            'address_detail.string' => 'Địa chỉ chi tiết phải là chuỗi ký tự.',
            'address_detail.max' => 'Địa chỉ chi tiết không được vượt quá 255 ký tự.',
            'email_change_code.digits' => 'Mã xác thực phải gồm đúng 6 chữ số.',
            'avatar.file' => 'Ảnh đại diện phải là một tệp hợp lệ.',
        ];
    }
}
