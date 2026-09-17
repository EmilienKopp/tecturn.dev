<?php

declare(strict_types=1);

namespace App\Http\Requests\Presentations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequestRehearsalReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization runs in the controller via the presentation gate.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reviewer_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
