<?php

namespace App\Http\Requests;

use App\Enums\ImagePurposeEnum;
use Illuminate\Foundation\Http\FormRequest;

class BrowserUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return filled($this->bearerToken());
    }

    public function rules(): array
    {
        return ['image' => ['required', ...ImagePurposeEnum::Product->uploadedFileRules()]];
    }
}
