<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['path' => $this->route('path')]);
    }

    public function rules(): array
    {
        return ['path' => ['required', 'string', 'max:1024']];
    }
}
