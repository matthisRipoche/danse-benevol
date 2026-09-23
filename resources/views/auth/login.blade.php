<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Connexion — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        <div class="flex min-h-screen items-center justify-center p-4 md:p-8">
            <main class="w-full max-w-md rounded-2xl border border-sand-200 bg-white p-6 shadow-[0_2px_6px_-1px_rgba(92,25,17,0.04),0_1px_3px_-1px_rgba(92,25,17,0.02)]">
                <div class="relative mb-6 overflow-hidden rounded-xl bg-brand-600 p-6 text-white">
                    <div class="pointer-events-none absolute -right-8 -bottom-10 size-44 rounded-full bg-brand-400/30 blur-2xl"></div>
                    <div class="pointer-events-none absolute -top-12 -left-10 size-32 rounded-full bg-sand-400/15 blur-2xl"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-xs font-semibold tracking-wider text-sand-400 uppercase">Salon de la Danse • Angers</span>
                        <h1 class="font-heading text-2xl font-bold">Bon retour parmi nous</h1>
                        <p class="text-sm text-white/80">Connecte-toi pour retrouver ton planning bénévole.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="flex flex-col gap-1">
                        <label for="email" class="text-sm font-medium text-stone-600">Adresse e-mail</label>
                        <div class="relative flex items-center">
                            <svg class="pointer-events-none absolute left-3 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                                class="h-11 w-full rounded-lg border border-sand-200 bg-white pr-3 pl-10 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('email') border-red-400 @enderror">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="password" class="text-sm font-medium text-stone-600">Mot de passe</label>
                        <div class="relative flex items-center">
                            <svg class="pointer-events-none absolute left-3 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                            <input type="password" name="password" id="password" required autocomplete="current-password"
                                class="h-11 w-full rounded-lg border border-sand-200 bg-white pr-11 pl-10 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('email') border-red-400 @enderror">
                            <button type="button" data-toggle-password="password" aria-label="Afficher le mot de passe"
                                class="absolute right-2 flex size-8 items-center justify-center rounded-full text-stone-400 hover:text-stone-700">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <label class="flex w-fit cursor-pointer items-center gap-2 text-sm text-stone-600">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                            class="size-4 rounded border-sand-400 accent-brand-500">
                        Se souvenir de moi
                    </label>

                    <button type="submit"
                        class="mt-2 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-brand-500 font-semibold text-white shadow-md transition hover:bg-brand-600 hover:shadow-lg active:scale-[0.99]">
                        Se connecter
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </form>

                <div class="mt-6 flex items-start gap-3 rounded-lg bg-sand-100 p-4 text-sm text-stone-600">
                    <svg class="mt-0.5 size-5 shrink-0 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3M17 6l3 3M14 9l2 2"/></svg>
                    <p>
                        Pas encore de compte ? L'inscription se fait avec le code d'invitation reçu par e-mail.
                        <a href="{{ route('register') }}" class="font-semibold text-brand-500 hover:underline">J'ai un code</a>
                    </p>
                </div>

                @if (app()->environment('local'))
                    <form method="POST" action="{{ route('dev-login.admin') }}" class="mt-6 border-t border-dashed border-sand-200 pt-4">
                        @csrf
                        <button type="submit"
                            class="w-full rounded-full border border-dashed border-sand-400 px-5 py-2 text-sm text-stone-500 hover:border-stone-500 hover:text-stone-700">
                            Connexion rapide admin (dev uniquement)
                        </button>
                    </form>
                @endif
            </main>
        </div>

        <script>
            document.querySelectorAll('[data-toggle-password]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.togglePassword);
                    const isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    button.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                });
            });
        </script>
    </body>
</html>
