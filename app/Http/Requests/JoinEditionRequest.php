<?php

namespace App\Http\Requests;

use App\Models\InvitationCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class JoinEditionRequest extends FormRequest
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
            'is_minor' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The code must be a pending, unexpired invitation sent to the logged-in volunteer's email,
     * for an edition they have not joined yet.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('code')) {
                return;
            }

            $invitationCode = InvitationCode::where('code', mb_strtoupper(trim($this->input('code'))))->first();

            $error = match (true) {
                ! $invitationCode => "Ce code d'invitation est introuvable.",
                $invitationCode->status !== 'pending' => "Ce code d'invitation a déjà été utilisé ou n'est plus valide.",
                $invitationCode->expires_at?->isPast() => "Ce code d'invitation a expiré.",
                $invitationCode->email && mb_strtolower($invitationCode->email) !== mb_strtolower($this->user()->email) => 'Ce code a été envoyé à une autre adresse e-mail que celle de ton compte.',
                $this->user()->isRegisteredFor($invitationCode->edition) => 'Tu es déjà inscrit(e) à cette édition.',
                default => null,
            };

            if ($error) {
                $validator->errors()->add('code', $error);
            }
        });
    }
}
