<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\FeatureFlags\FeatureCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeatureFlagRequest extends FormRequest
{
    /**
     * Access is already gated by the admin middleware on the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $flag = FeatureCatalog::find((string) $this->input('key'));

        $options = $flag !== null ? array_column($flag->options, 'value') : [];

        $valueRule = $flag !== null && $flag->isBoolean()
            ? ['required', 'boolean']
            : ['required', 'string', Rule::in($options)];

        $isTeamScoped = $flag !== null && ! $flag->isGlobal();

        return [
            'key' => ['required', 'string', Rule::in(array_map(fn ($f) => $f->key, FeatureCatalog::all()))],
            'value' => $valueRule,
            'team_id' => [
                $isTeamScoped ? 'required' : 'nullable',
                'integer',
                'exists:teams,id',
            ],
        ];
    }

    /**
     * The validated value coerced to the flag's PHP type (bool for toggles).
     */
    public function resolvedValue(): bool|string
    {
        $flag = FeatureCatalog::find($this->validated('key'));

        if ($flag !== null && $flag->isBoolean()) {
            return $this->boolean('value');
        }

        return (string) $this->validated('value');
    }

    public function teamId(): ?int
    {
        $teamId = $this->validated('team_id');

        return $teamId !== null ? (int) $teamId : null;
    }
}
