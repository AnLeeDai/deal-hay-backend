<?php

namespace App\Http\Requests;

use App\Services\RewardedTaskService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use JsonException;

class AdGemPostbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        abort_unless(app(RewardedTaskService::class)->isEnabled(), 503);
        $key = (string) config('rewarded_tasks.adgem.postback_key');
        $signature = $this->header('Signature', '');
        abort_unless(is_string($signature) && hash_equals(hash_hmac('sha256', $this->getContent(), $key), $signature), 401);
        $allowedIps = config('rewarded_tasks.adgem.postback_ips', []);
        abort_if($allowedIps !== [] && ! in_array($this->ip(), $allowedIps, true), 403);

        return true;
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        try {
            $payload = json_decode($this->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            abort(400, 'Invalid JSON.');
        }

        abort_unless(is_array($payload), 400, 'Invalid JSON.');

        return $payload;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'request_id' => ['required', 'string', 'max:255'],
            'timestamp' => ['required', 'integer', 'min:1'],
            'data' => ['required', 'array'],
            'data.app_id' => ['required', 'string', 'max:100'],
            'data.player_id' => ['required', 'string', 'max:64'],
            'data.c1' => ['required', 'uuid'],
            'data.c2' => ['required', 'string', 'size:64'],
            'data.conversion_id' => ['required', 'string', 'max:255'],
            'data.goal_id' => ['nullable', 'string', 'max:255'],
            'data.campaign_id' => ['nullable', 'string', 'max:255'],
            'data.offer_id' => ['nullable', 'string', 'max:255'],
            'data.conversion_type' => ['required', 'string', 'in:reward,install'],
            'data.payout' => ['required', 'numeric', 'between:-99999999999,99999999999'],
            'data.amount' => ['required', 'numeric', 'between:-99999999999,99999999999'],
        ];
    }
}
