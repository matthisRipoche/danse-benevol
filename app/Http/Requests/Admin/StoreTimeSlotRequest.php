<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTimeSlotRequest extends FormRequest
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
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ];
    }

    /**
     * Configure the validator instance: a time slot must not overlap another one of the same day.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $overlaps = $this->route('eventDay')->timeSlots()
                ->where('starts_at', '<', $this->input('ends_at').':00')
                ->where('ends_at', '>', $this->input('starts_at').':00')
                ->exists();

            if ($overlaps) {
                $validator->errors()->add('starts_at', 'Ce créneau chevauche un créneau existant de la même journée.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_at.required' => 'Indique l\'heure de début.',
            'ends_at.required' => 'Indique l\'heure de fin.',
            'starts_at.date_format' => 'L\'heure de début est invalide.',
            'ends_at.date_format' => 'L\'heure de fin est invalide.',
            'ends_at.after' => 'L\'heure de fin doit être après l\'heure de début.',
        ];
    }
}
