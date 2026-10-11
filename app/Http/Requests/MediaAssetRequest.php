<?php

namespace App\Http\Requests;

use Closure;
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
        return ['path' => [
            'required',
            'string',
            'max:1024',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }

                $path = $value;

                do {
                    $segments = explode('/', $path);

                    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)
                        || preg_match('/[\\\\\\x00-\\x1f\\x7f?#]/', $path)
                        || array_intersect($segments, ['', '.', '..']) !== []
                        || in_array($segments[0], ['chat-media', 'staged-images'], true)) {
                        $fail('The requested media path is not publicly accessible.');

                        return;
                    }

                    $decodedPath = rawurldecode($path);

                    if ($decodedPath === $path) {
                        return;
                    }

                    $path = $decodedPath;
                } while (true);
            },
        ]];
    }
}
