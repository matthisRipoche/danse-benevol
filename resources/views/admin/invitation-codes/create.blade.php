<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Nouveau code d'invitation — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <a href="{{ route('admin.invitation-codes.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-brand-500">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Retour à la liste
            </a>

            <div class="max-w-md rounded-xl border border-sand-200 bg-white p-6">
                <h1 class="mb-1 font-heading text-xl font-bold">Nouveau code d'invitation</h1>
                <p class="mb-5 text-sm text-stone-500">Le code est envoyé à cet e-mail et ne pourra servir qu'à lui.</p>

                @include('admin.partials.flash')

                <form method="POST" action="{{ route('admin.invitation-codes.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="flex flex-col gap-1">
                        <label for="email" class="text-sm font-medium text-stone-600">E-mail du candidat</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                            class="h-11 w-full rounded-lg border border-sand-200 bg-white px-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('email') border-red-400 @enderror">
                    </div>

                    <button type="submit" class="h-11 rounded-full bg-brand-500 font-semibold text-white shadow-sm hover:bg-brand-600">
                        Créer le code
                    </button>
                </form>
            </div>
        </main>
    </body>
</html>
