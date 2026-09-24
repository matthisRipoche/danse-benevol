<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Inscription — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        <div class="flex min-h-screen items-center justify-center p-4 md:p-8">
            <main class="w-full max-w-md rounded-2xl border border-sand-200 bg-white p-6 shadow-[0_2px_6px_-1px_rgba(92,25,17,0.04),0_1px_3px_-1px_rgba(92,25,17,0.02)]">
                <div class="relative mb-6 overflow-hidden rounded-xl bg-brand-600 p-6 text-white">
                    <div class="pointer-events-none absolute -right-8 -bottom-10 size-44 rounded-full bg-brand-400/30 blur-2xl"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-xs font-semibold tracking-wider text-sand-400 uppercase">Salon de la Danse • Angers</span>
                        <h1 class="font-heading text-2xl font-bold">Créer mon compte bénévole</h1>
                        <p class="text-sm text-white/80">Ton code d'invitation te permet de rejoindre l'équipe bénévole.</p>
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

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                    @csrf

                    <div class="flex flex-col gap-1 rounded-lg bg-sand-100 p-4">
                        <label for="code" class="flex items-center gap-1.5 text-sm font-semibold">
                            <svg class="size-4 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3M17 6l3 3M14 9l2 2"/></svg>
                            Code d'invitation
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code', $code) }}" required autocomplete="off"
                            class="h-11 w-full rounded-lg border border-sand-200 bg-white px-3.5 text-center font-mono tracking-widest uppercase focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('code') border-red-400 @enderror">
                    </div>

                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-3 flex items-center gap-2 font-heading font-semibold">
                            <span class="flex size-6 items-center justify-center rounded-full bg-sand-100 text-xs font-bold text-brand-500">1</span>
                            Coordonnées personnelles
                        </legend>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1">
                                <label for="first_name" class="text-sm font-medium text-stone-600">Prénom</label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required autocomplete="given-name"
                                    class="h-11 w-full rounded-lg border border-sand-200 bg-white px-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('first_name') border-red-400 @enderror">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="last_name" class="text-sm font-medium text-stone-600">Nom</label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"
                                    class="h-11 w-full rounded-lg border border-sand-200 bg-white px-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('last_name') border-red-400 @enderror">
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="email" class="text-sm font-medium text-stone-600">Adresse e-mail</label>
                            <div class="relative flex items-center">
                                <svg class="pointer-events-none absolute left-3 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                                    class="h-11 w-full rounded-lg border border-sand-200 bg-white pr-3 pl-10 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('email') border-red-400 @enderror">
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="phone" class="text-sm font-medium text-stone-600">Numéro de téléphone</label>
                            <div class="relative flex items-center">
                                <svg class="pointer-events-none absolute left-3 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/></svg>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required autocomplete="tel"
                                    class="h-11 w-full rounded-lg border border-sand-200 bg-white pr-3 pl-10 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('phone') border-red-400 @enderror">
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-start gap-2.5 rounded-lg bg-sand-50 p-3 text-sm">
                            <input type="checkbox" name="is_minor" value="1" @checked(old('is_minor'))
                                class="mt-0.5 size-4 shrink-0 accent-brand-500">
                            <span>
                                <span class="font-semibold">J'ai moins de 18 ans</span>
                                <span class="block text-stone-500">Ton profil sera vérifié par l'organisation avant la validation de ton planning.</span>
                            </span>
                        </label>
                    </fieldset>

                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-3 flex items-center gap-2 font-heading font-semibold">
                            <span class="flex size-6 items-center justify-center rounded-full bg-sand-100 text-xs font-bold text-brand-500">2</span>
                            Sécurité du compte
                        </legend>

                        @foreach (['password' => 'Mot de passe', 'password_confirmation' => 'Confirmer le mot de passe'] as $passwordField => $passwordLabel)
                            <div class="flex flex-col gap-1">
                                <label for="{{ $passwordField }}" class="text-sm font-medium text-stone-600">{{ $passwordLabel }}</label>
                                <div class="relative flex items-center">
                                    <input type="password" name="{{ $passwordField }}" id="{{ $passwordField }}" required autocomplete="new-password"
                                        class="h-11 w-full rounded-lg border border-sand-200 bg-white pr-11 pl-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('password') border-red-400 @enderror">
                                    <button type="button" data-toggle-password="{{ $passwordField }}" aria-label="Afficher le mot de passe"
                                        class="absolute right-2 flex size-8 items-center justify-center rounded-full text-stone-400 hover:text-stone-700">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        <p class="text-xs text-stone-500">8 caractères minimum.</p>
                    </fieldset>

                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-3 flex items-center gap-2 font-heading font-semibold">
                            <span class="flex size-6 items-center justify-center rounded-full bg-sand-100 text-xs font-bold text-brand-500">3</span>
                            Photo pour le badge (obligatoire)
                        </legend>

                        <div class="flex items-center gap-4 rounded-xl bg-sand-100 p-4">
                            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-sand-200 text-sand-400">
                                <img id="photo-preview" alt="Aperçu de la photo" class="hidden size-full object-cover">
                                <svg id="photo-placeholder" class="size-10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0Z"/></svg>
                            </div>
                            <div class="flex min-w-0 flex-col gap-1">
                                <p class="font-semibold">Photo d'identité nette</p>
                                <p class="text-sm text-stone-600">De face, tête nue, en regardant l'objectif. JPG ou PNG, 6 Mo maximum.</p>
                                <label for="photo" class="mt-1 inline-flex w-fit cursor-pointer items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-semibold shadow-sm hover:bg-sand-50 has-[:focus-visible]:ring-3 has-[:focus-visible]:ring-brand-500/30 @error('photo') ring-2 ring-red-400 @enderror">
                                    <svg class="size-4 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z"/><circle cx="12" cy="13" r="3"/></svg>
                                    <span id="photo-label">Choisir une photo</span>
                                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png" required class="sr-only">
                                </label>
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex flex-col gap-3">
                        <button type="submit"
                            class="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-brand-500 font-semibold text-white shadow-md transition hover:bg-brand-600 hover:shadow-lg active:scale-[0.99]">
                            Créer mon compte et accéder au planning
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </button>
                        <p class="text-center text-sm text-stone-600">
                            Déjà inscrit(e) ?
                            <a href="{{ route('login') }}" class="font-semibold text-brand-500 hover:underline">Se connecter</a>
                        </p>
                    </div>
                </form>
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

            document.getElementById('photo').addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (! file) {
                    return;
                }
                const preview = document.getElementById('photo-preview');
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
                document.getElementById('photo-placeholder').classList.add('hidden');
                document.getElementById('photo-label').textContent = file.name;
            });
        </script>
    </body>
</html>
