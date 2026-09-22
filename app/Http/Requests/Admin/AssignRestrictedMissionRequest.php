<?php

namespace App\Http\Requests\Admin;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignRestrictedMissionRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('email')) {
                return;
            }

            $volunteer = User::where('email', $this->input('email'))->first();

            if (! $volunteer) {
                $validator->errors()->add('email', 'Aucun compte ne correspond à cet e-mail.');

                return;
            }

            $isRegistered = $volunteer->editions()
                ->where('editions.id', Edition::active()->id)
                ->exists();

            if (! $isRegistered) {
                $validator->errors()->add('email', "Ce bénévole n'est pas inscrit à l'édition active.");
            }
        });
    }
}
