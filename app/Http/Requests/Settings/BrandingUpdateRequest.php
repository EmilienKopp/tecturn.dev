<?php

namespace App\Http\Requests\Settings;

use App\Support\Branding;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BrandingUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return Branding::rules();
    }

    /**
     * The validated branding palette, ready to store on the user.
     *
     * @return array<string, string>
     */
    public function palette(): array
    {
        /** @var array<string, mixed> $branding */
        $branding = $this->validated('branding');

        return Branding::merge($branding);
    }
}
