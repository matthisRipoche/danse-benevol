<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the "forgotten password" form.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given email.
     *
     * The response is identical whether or not an account exists (or a link was just sent),
     * so the form cannot be used to find out which emails are registered.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->only('email'));

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'Si un compte correspond à cette adresse, tu vas recevoir un e-mail avec un lien pour choisir un nouveau mot de passe.');
    }
}
