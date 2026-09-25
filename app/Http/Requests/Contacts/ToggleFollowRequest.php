<?php

namespace App\Http\Requests\Contacts;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ToggleFollowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $routeUser = $this->route('user');

        return $this->user() !== null
            && $routeUser instanceof User
            && $this->user()->id !== $routeUser->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
