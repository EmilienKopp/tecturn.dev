<?php

namespace App\Http\Requests\Presentations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreatePresentationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'source_type' => ['sometimes', 'in:editor,pdf,google_slides'],
            'external_url' => [
                'required_if:source_type,google_slides',
                'nullable',
                'url',
                'starts_with:https://docs.google.com/presentation/',
            ],
            'file' => [
                'required_if:source_type,pdf',
                'nullable',
                'file',
                'mimes:pdf',
                'max:25600',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'external_url.starts_with' => 'Enter a Google Slides link (docs.google.com/presentation/...).',
            'file.required_if' => 'Choose a PDF to upload.',
            'file.mimes' => 'The uploaded file must be a PDF.',
        ];
    }
}
