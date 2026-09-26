<?php

namespace App\Http\Requests\Settings;

use App\Ai\DecksterModels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Laravel\Ai\Enums\Lab;

/**
 * Validates a new "bring your own AI" credential. The driver must be one we
 * support; a base URL is required for the openai-compatible driver; and the API
 * key is required for every driver except openai-compatible (where an endpoint
 * may be keyless).
 */
class AiCredentialStoreRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $driver = (string) $this->input('driver');

        return [
            'label' => ['nullable', 'string', 'max:100'],
            'driver' => ['required', 'string', Rule::in(DecksterModels::freetextDrivers())],
            'model' => ['required', 'string', 'max:200'],
            'base_url' => [
                Rule::requiredIf(DecksterModels::requiresBaseUrl($driver)),
                'nullable',
                'url',
                'max:2048',
            ],
            'api_key' => [
                Rule::requiredIf($driver !== Lab::OpenAICompatible->value),
                'nullable',
                'string',
                'max:500',
            ],
            'is_default' => ['boolean'],
        ];
    }

    /**
     * Normalize the credential ready to persist.
     *
     * @return array<string, mixed>
     */
    public function credential(): array
    {
        return [
            'label' => $this->string('label')->trim()->value() ?: null,
            'driver' => (string) $this->input('driver'),
            'model' => $this->string('model')->trim()->value(),
            'base_url' => $this->string('base_url')->trim()->value() ?: null,
            'api_key' => (string) $this->input('api_key', ''),
            'is_default' => $this->boolean('is_default'),
        ];
    }
}
