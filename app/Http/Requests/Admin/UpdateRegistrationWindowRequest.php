<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRegistrationWindowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    /**
     * Both dates are optional: an empty one leaves that side of the window open.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'registration_opens_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'registration_closes_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after:registration_opens_at'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_closes_at.after' => "La fermeture des inscriptions doit être postérieure à l'ouverture.",
        ];
    }
}
