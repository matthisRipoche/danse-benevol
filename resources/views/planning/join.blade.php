<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Rejoindre {{ $edition->name }} — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('partials.volunteer-header')

        <div class="flex justify-center p-4 md:p-8">
            <main class="w-full max-w-md rounded-2xl border border-sand-200 bg-white p-6 shadow-[0_2px_6px_-1px_rgba(92,25,17,0.04),0_1px_3px_-1px_rgba(92,25,17,0.02)]">
                <div class="relative mb-6 overflow-hidden rounded-xl bg-brand-600 p-6 text-white">
                    <div class="pointer-events-none absolute -right-8 -bottom-10 size-44 rounded-full bg-brand-400/30 blur-2xl"></div>
                    <div class="pointer-events-none absolute -top-12 -left-10 size-32 rounded-full bg-sand-400/15 blur-2xl"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-xs font-semibold tracking-wider text-sand-400 uppercase">{{ $edition->name }}</span>
                        <h1 class="font-heading text-2xl font-bold">Content de te revoir, {{ $user->first_name }} !</h1>
                        <p class="text-sm text-white/80">Pour participer à cette édition, saisis le code d'invitation reçu par e-mail.</p>
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

                <form method="POST" action="{{ route('edition.join.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="flex flex-col gap-1 rounded-lg bg-sand-100 p-4">
                        <label for="code" class="flex items-center gap-1.5 text-sm font-semibold">
                            <svg class="size-4 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3M17 6l3 3M14 9l2 2"/></svg>
                            Code d'invitation
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code', $code) }}" required autocomplete="off" @unless ($code) autofocus @endunless
                            class="h-11 w-full rounded-lg border border-sand-200 bg-white px-3.5 text-center font-mono tracking-widest uppercase focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('code') border-red-400 @enderror">
                    </div>

                    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg bg-sand-50 p-3 text-sm">
                        <input type="checkbox" name="is_minor" value="1" @checked(old('is_minor', $user->is_minor))
                            class="mt-0.5 size-4 shrink-0 accent-brand-500">
                        <span>
                            <span class="font-semibold">J'aurai moins de 18 ans pendant le salon</span>
                            <span class="block text-stone-500">Ton profil sera vérifié par l'organisation avant la validation de ton planning, comme chaque année.</span>
                        </span>
                    </label>

                    <p class="text-sm text-stone-600">
                        Ton compte, ton mot de passe et ta photo restent les mêmes. Tu pourras mettre à jour tes informations depuis <strong>Mon profil</strong> jusqu'à la validation de ton nouveau planning.
                    </p>

                    <button type="submit"
                        class="mt-2 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-brand-500 font-semibold text-white shadow-md transition hover:bg-brand-600 hover:shadow-lg active:scale-[0.99]">
                        Rejoindre l'édition
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </form>
            </main>
        </div>
    </body>
</html>
