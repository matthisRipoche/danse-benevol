<?php

namespace App\Http\Requests\Admin;

use App\Models\Edition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('missions', 'name')->where('edition_id', Edition::active()->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['required', 'boolean'],
            'is_adult_only' => ['sometimes', 'boolean'],
            'default_capacity' => ['required', 'integer', 'min:0', 'max:500'],
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
            'name.required' => 'Donne un nom à la mission.',
            'name.unique' => 'Une mission porte déjà ce nom sur cette édition.',
            'default_capacity.required' => 'Indique le nombre de places par créneau.',
            'default_capacity.integer' => 'Le nombre de places doit être un nombre entier.',
            'default_capacity.min' => 'Le nombre de places ne peut pas être négatif.',
            'default_capacity.max' => 'Le nombre de places ne peut pas dépasser 500.',
        ];
    }
}
