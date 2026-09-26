<?php

namespace App\Http\Requests\Presentations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateDeckRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', 'max:20000'],
            'name' => ['nullable', 'string', 'max:255'],
            // The chosen AI model: null/absent = free house model, an id = one of
            // the user's own credentials. Must belong to the requesting user.
            'ai_credential_id' => [
                'nullable',
                'integer',
                Rule::exists('user_ai_credentials', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
