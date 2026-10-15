<?php

namespace App\Rules;

use App\Services\AddressService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidProvince implements ValidationRule
{
    public function __construct(private readonly bool $useCodes = false) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app(AddressService::class)->provinceCode($value, $this->useCodes) === null) {
            $fail('Vui lòng chọn tỉnh/thành từ danh sách.');
        }
    }
}
