<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterVolunteerRequest;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the volunteer registration form.
     */
    public function create(Request $request): View
    {
        return view('auth.register', [
            'code' => $request->query('code'),
        ]);
    }

    /**
     * Handle a volunteer registration request.
     */
    public function store(RegisterVolunteerRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $invitationCode = InvitationCode::where('code', $request->validated('code'))
                ->lockForUpdate()
                ->first();

            if (! $invitationCode || $invitationCode->status !== 'pending') {
                throw ValidationException::withMessages([
                    'code' => "Ce code d'invitation a déjà été utilisé ou n'est plus valide.",
                ]);
            }

            $photoPath = $request->file('photo')->store('photos', 'local');

            $user = User::create([
                'first_name' => $request->validated('first_name'),
                'last_name' => $request->validated('last_name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password'),
                'photo_path' => $photoPath,
                'role' => 'volunteer',
            ]);

            $invitationCode->update([
                'status' => 'used',
                'used_by_user_id' => $user->id,
                'used_at' => now(),
            ]);

            $user->editions()->attach($invitationCode->edition_id);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('register.confirmation');
    }
}
