<?php

namespace App\Http\Requests\Admin;

use App\Models\Edition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class UpdateEditionRequest extends StoreEditionRequest
{
    /**
     * Same fields as the creation form, without the copy option.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return collect(parent::rules())->except('copy_from_edition_id')->all();
    }

    /**
     * The new dates must still contain every day already created for the edition.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['start_date', 'end_date'])) {
                return;
            }

            /** @var Edition $edition */
            $edition = $this->route('edition');
            $outsideDays = $edition->eventDays()
                ->where(fn ($query) => $query
                    ->whereDate('date', '<', $this->input('start_date'))
                    ->orWhereDate('date', '>', $this->input('end_date')))
                ->count();

            if ($outsideDays > 0) {
                $validator->errors()->add('start_date', "{$outsideDays} jour(s) déjà créé(s) tomberaient en dehors de ces dates : supprime-les d'abord dans « Jours et créneaux ».");
            }
        });
    }
}
