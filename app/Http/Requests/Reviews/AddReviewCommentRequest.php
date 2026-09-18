<?php

declare(strict_types=1);

namespace App\Http\Requests\Reviews;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddReviewCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization runs in the controller via the review policy.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slide_number' => ['required', 'integer', 'min:0'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
