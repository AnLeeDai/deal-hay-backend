<?php

namespace App\Rules;

use App\Enums\ImagePurposeEnum;
use App\Services\ImageService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class PreparedWebp implements ValidationRule
{
    public function __construct(private readonly ImagePurposeEnum $purpose) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Vui lòng gửi một tệp ảnh WebP hợp lệ.');

            return;
        }
        try {
            app(ImageService::class)->validateFile($value, $this->purpose, $attribute);
        } catch (ValidationException $exception) {
            $fail($exception->errors()[$attribute][0]);
        }
    }
}
