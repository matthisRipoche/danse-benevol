<?php

namespace App\Http\Requests\Auth;

use App\Models\InvitationCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterVolunteerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:6144'],
            'is_minor' => ['nullable', 'boolean'],
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
            'email.unique' => "Un compte existe déjà avec cette adresse e-mail : connecte-toi, puis saisis ton code pour rejoindre l'édition.",
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('code')) {
                return;
            }

            $invitationCode = InvitationCode::where('code', $this->input('code'))->first();

            if (! $invitationCode) {
                $validator->errors()->add('code', "Ce code d'invitation est introuvable.");

                return;
            }

            if ($invitationCode->status !== 'pending') {
                $validator->errors()->add('code', "Ce code d'invitation a déjà été utilisé ou n'est plus valide.");

                return;
            }

            if ($invitationCode->expires_at && $invitationCode->expires_at->isPast()) {
                $validator->errors()->add('code', "Ce code d'invitation a expiré.");

                return;
            }

            if ($invitationCode->email && ! $validator->errors()->has('email') && $invitationCode->email !== $this->input('email')) {
                $validator->errors()->add('email', "Cet e-mail ne correspond pas au code d'invitation.");
            }
        });
    }
}
