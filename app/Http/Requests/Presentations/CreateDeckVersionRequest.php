<?php

declare(strict_types=1);

namespace App\Http\Requests\Presentations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDeckVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bump' => ['required', 'string', Rule::in(['major', 'minor'])],
        ];
    }

    /** @return 'major'|'minor' */
    public function bump(): string
    {
        /** @var 'major'|'minor' */
        return (string) $this->validated('bump');
    }
}
