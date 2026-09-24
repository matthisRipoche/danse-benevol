<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEditionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('editions', 'name')->ignore($this->route('edition'))],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'min_slots_per_volunteer' => ['required', 'integer', 'min:1', 'max:20'],
            'max_slots_per_volunteer' => ['required', 'integer', 'gte:min_slots_per_volunteer', 'max:20'],
            'max_consecutive_slots' => ['required', 'integer', 'min:1', 'max:20'],
            'copy_from_edition_id' => ['nullable', 'integer', Rule::exists('editions', 'id')],
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
            'name.unique' => 'Une édition porte déjà ce nom.',
            'end_date.after_or_equal' => 'La date de fin doit être le même jour que la date de début ou après.',
            'max_slots_per_volunteer.gte' => 'Le maximum de créneaux doit être supérieur ou égal au minimum.',
        ];
    }
}
