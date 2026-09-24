<?php

namespace App\Http\Requests\Admin;

use App\Models\Edition;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationCodeRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) {
                    $existingUser = User::where('email', $value)->first();

                    if ($existingUser?->role === 'admin') {
                        $fail("Cette adresse est celle d'un compte administrateur.");
                    } elseif ($existingUser?->isRegisteredFor(Edition::active())) {
                        $fail("Ce bénévole est déjà inscrit à l'édition en cours.");
                    }
                },
                Rule::unique('invitation_codes', 'email')
                    ->where('edition_id', Edition::active()->id)
                    ->where('status', 'pending'),
            ],
        ];
    }
}
