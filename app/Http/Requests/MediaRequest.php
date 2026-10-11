<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validate transport shape only; media policy is enforced by image-services. */
class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'storeStaged', 'storeUploadSessions' => ['uploads' => ['required', 'array']],
            'prepareStaged' => ['token' => ['required', 'string'], 'image' => ['required', 'file']],
            'discardStaged' => ['tokens' => ['required', 'array'], 'tokens.*' => ['string']],
            'transform' => ['size' => ['required', 'integer'], 'purpose' => ['required', 'string']],
            default => ['avatar' => ['required', 'file']],
        };
    }
}
