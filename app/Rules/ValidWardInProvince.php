<?php

namespace App\Rules;

use App\Services\AddressService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidWardInProvince implements ValidationRule
{
    public function __construct(private readonly mixed $province, private readonly bool $useCodes = false) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(AddressService::class)->wardBelongsToProvince($value, $this->province, $this->useCodes)) {
            $fail('Vui lòng chọn phường/xã thuộc tỉnh/thành đã chọn.');
        }
    }
}
