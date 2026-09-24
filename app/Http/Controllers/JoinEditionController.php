<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinEditionRequest;
use App\Models\Edition;
use App\Models\InvitationCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JoinEditionController extends Controller
{
    /**
     * Display the form a returning volunteer fills with the code received for a new edition.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $edition = Edition::active();

        if ($request->user()->isRegisteredFor($edition)) {
            return redirect()->route('planning.index');
        }

        return view('planning.join', [
            'edition' => $edition,
            'code' => $request->query('code'),
            'user' => $request->user(),
        ]);
    }

    /**
     * Register the logged-in volunteer for the code's edition. A new edition is a fresh start:
     * the profile is unlocked again and a minor's validation must be given again for this year.
     */
    public function store(JoinEditionRequest $request): RedirectResponse
    {
        $user = $request->user();

        $edition = DB::transaction(function () use ($request, $user) {
            $invitationCode = InvitationCode::where('code', mb_strtoupper(trim($request->validated('code'))))
                ->lockForUpdate()
                ->first();

            if (! $invitationCode || $invitationCode->status !== 'pending') {
                throw ValidationException::withMessages([
                    'code' => "Ce code d'invitation a déjà été utilisé ou n'est plus valide.",
                ]);
            }

            $invitationCode->update([
                'status' => 'used',
                'used_by_user_id' => $user->id,
                'used_at' => now(),
            ]);

            $user->editions()->attach($invitationCode->edition_id);

            $user->forceFill([
                'is_minor' => $request->boolean('is_minor'),
                'minor_validated_at' => null,
                'profile_locked_at' => null,
            ])->save();

            return $invitationCode->edition;
        });

        return redirect()->route('planning.index')
            ->with('status', "Bienvenue pour {$edition->name} ! Tu peux composer ton planning ci-dessous.");
    }
}
