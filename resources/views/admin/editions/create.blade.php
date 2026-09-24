<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Nouvelle édition — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <a href="{{ route('admin.editions.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-brand-500">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Retour aux éditions
            </a>

            <div class="max-w-xl rounded-xl border border-sand-200 bg-white p-6">
                <h1 class="font-heading text-xl font-bold">Nouvelle édition</h1>
                <p class="mt-1 mb-5 text-sm text-stone-500">Elle est créée en brouillon : l'édition active ne change pas tant que tu ne l'actives pas.</p>

                @include('admin.partials.flash')

                <form method="POST" action="{{ route('admin.editions.store') }}" class="flex flex-col gap-5">
                    @csrf

                    @include('admin.editions.partials.fields', ['edition' => null])

                    <div class="flex flex-col gap-1">
                        <label for="copy_from_edition_id" class="text-sm font-medium text-stone-600">Reprendre la grille d'une édition précédente <span class="text-stone-400">(facultatif)</span></label>
                        <select name="copy_from_edition_id" id="copy_from_edition_id"
                            class="h-11 w-full rounded-lg border border-sand-200 bg-white px-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none">
                            <option value="">Partir d'une grille vide</option>
                            @foreach ($sourceEditions as $sourceEdition)
                                <option value="{{ $sourceEdition->id }}" @selected((int) old('copy_from_edition_id') === $sourceEdition->id)>{{ $sourceEdition->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-stone-500">Copie les jours (décalés sur les nouvelles dates), les créneaux horaires, les missions et leurs places. Les bénévoles et leurs réservations ne sont jamais copiés.</p>
                    </div>

                    <button type="submit" class="h-11 rounded-full bg-brand-500 font-semibold text-white shadow-sm hover:bg-brand-600 sm:w-fit sm:px-6">
                        Créer l'édition
                    </button>
                </form>
            </div>
        </main>
    </body>
</html>
