<?php

namespace App\Http\Requests\Admin;

use App\Models\Edition;
use App\Models\EventDay;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventDayRequest extends FormRequest
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
        $edition = Edition::active();

        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$edition->start_date->format('Y-m-d'),
                'before_or_equal:'.$edition->end_date->format('Y-m-d'),
                function (string $attribute, mixed $value, Closure $fail) use ($edition) {
                    if (EventDay::where('edition_id', $edition->id)->whereDate('date', $value)->exists()) {
                        $fail('Ce jour existe déjà.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $edition = Edition::active();

        return [
            'date.required' => 'Choisis une date.',
            'date.date_format' => 'La date est invalide.',
            'date.after_or_equal' => 'Le jour doit être compris dans les dates de l\'édition (du '.$edition->start_date->format('d/m/Y').' au '.$edition->end_date->format('d/m/Y').').',
            'date.before_or_equal' => 'Le jour doit être compris dans les dates de l\'édition (du '.$edition->start_date->format('d/m/Y').' au '.$edition->end_date->format('d/m/Y').').',
        ];
    }
}
