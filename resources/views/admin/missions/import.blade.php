<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Importer des missions — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <a href="{{ route('admin.missions.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-brand-500">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Retour aux missions
            </a>

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-5">
                <section class="rounded-xl border border-sand-200 bg-white p-6 lg:col-span-3">
                    <h1 class="mb-1 font-heading text-xl font-bold">Importer des missions</h1>
                    <p class="mb-5 text-sm text-stone-500">Une mission est créée par ligne du fichier, ouverte sur tous les créneaux de l'édition avec la capacité indiquée.</p>

                    @include('admin.partials.flash')

                    @if ($timeSlotCount === 0)
                        <p class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Aucun créneau horaire pour l'instant : les missions importées s'ouvriront automatiquement sur les créneaux que tu créeras ensuite.
                        </p>
                    @endif

                    <form method="POST" action="{{ route('admin.missions.import.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                        @csrf

                        <div class="flex flex-col gap-1">
                            <label for="file" class="text-sm font-medium text-stone-600">Fichier Excel ou CSV</label>
                            <input type="file" name="file" id="file" accept=".xlsx,.csv" required
                                class="w-full rounded-lg border border-sand-200 bg-white p-2 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-sand-100 file:px-4 file:py-1.5 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-sand-200 @error('file') border-red-400 @enderror">
                        </div>

                        <button type="submit" class="h-11 rounded-full bg-brand-500 font-semibold text-white shadow-sm hover:bg-brand-600 sm:w-fit sm:px-6">
                            Importer les missions
                        </button>
                    </form>
                </section>

                <aside class="rounded-xl border border-sand-200 bg-white p-6 text-sm lg:col-span-2">
                    <h2 class="mb-3 font-heading font-semibold">Format attendu</h2>
                    <div class="mb-4 overflow-x-auto rounded-lg border border-sand-200">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-sand-100/60 text-stone-500">
                                <tr><th class="px-2 py-1.5">Mission</th><th class="px-2 py-1.5">Description</th><th class="px-2 py-1.5">Publique</th><th class="px-2 py-1.5">Capacité</th><th class="px-2 py-1.5">Interdite aux mineurs</th></tr>
                            </thead>
                            <tbody class="divide-y divide-sand-100">
                                <tr><td class="px-2 py-1.5">Vestiaires</td><td class="px-2 py-1.5">Niveau -1</td><td class="px-2 py-1.5">Oui</td><td class="px-2 py-1.5">4</td><td class="px-2 py-1.5"></td></tr>
                                <tr><td class="px-2 py-1.5">Billetterie</td><td class="px-2 py-1.5"></td><td class="px-2 py-1.5">Non</td><td class="px-2 py-1.5">2</td><td class="px-2 py-1.5">Oui</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <ul class="flex list-inside list-disc flex-col gap-1.5 text-stone-600">
                        <li>Fichier <strong>.xlsx</strong> ou <strong>.csv</strong> (séparateur « ; » ou « , »), 2 Mo et {{ $maxRows }} lignes maximum.</li>
                        <li>Colonnes dans cet ordre ; la ligne d'en-tête est facultative.</li>
                        <li><strong>Publique</strong> : Oui ou Non (vide = Oui). Non = poste restreint, attribué par un admin.</li>
                        <li><strong>Interdite aux mineurs</strong> (facultative) : Oui ou Non (vide = Non).</li>
                        <li><strong>Capacité</strong> : places par créneau, de 0 à 500, ajustables ensuite mission par mission.</li>
                        <li>Les missions dont le nom existe déjà sont ignorées.</li>
                    </ul>
                </aside>
            </div>
        </main>
    </body>
</html>
