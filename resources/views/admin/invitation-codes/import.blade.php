<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Importer des candidats — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <a href="{{ route('admin.invitation-codes.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-brand-500">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Retour aux codes
            </a>

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-5">
                <section class="rounded-xl border border-sand-200 bg-white p-6 lg:col-span-3">
                    <h1 class="mb-1 font-heading text-xl font-bold">Importer des candidats</h1>
                    <p class="mb-5 text-sm text-stone-500">Un code d'invitation est créé et envoyé par e-mail à chaque candidat du fichier.</p>

                    @include('admin.partials.flash')

                    <form method="POST" action="{{ route('admin.invitation-codes.import.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                        @csrf

                        <div class="flex flex-col gap-1">
                            <label for="file" class="text-sm font-medium text-stone-600">Fichier Excel ou CSV</label>
                            <input type="file" name="file" id="file" accept=".xlsx,.csv" required
                                class="w-full rounded-lg border border-sand-200 bg-white p-2 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-sand-100 file:px-4 file:py-1.5 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-sand-200 @error('file') border-red-400 @enderror">
                        </div>

                        <button type="submit" class="h-11 rounded-full bg-brand-500 font-semibold text-white shadow-sm hover:bg-brand-600 sm:w-fit sm:px-6">
                            Importer et envoyer les codes
                        </button>
                    </form>
                </section>

                <aside class="rounded-xl border border-sand-200 bg-white p-6 text-sm lg:col-span-2">
                    <h2 class="mb-3 font-heading font-semibold">Format attendu</h2>
                    <ul class="flex list-inside list-disc flex-col gap-1.5 text-stone-600">
                        <li>Fichier <strong>.xlsx</strong> ou <strong>.csv</strong> (séparateur « ; » ou « , »), 2 Mo maximum.</li>
                        <li>Les e-mails dans la <strong>première colonne</strong>, un par ligne. Les autres colonnes sont ignorées.</li>
                        <li>Une ligne d'en-tête (« E-mail ») est acceptée et ignorée.</li>
                        <li>{{ $maxRows }} lignes maximum par import.</li>
                    </ul>
                    <p class="mt-4 rounded-lg bg-sand-50 p-3 text-stone-600">
                        Sont ignorés, avec le motif affiché après l'import : les adresses invalides, les doublons, les e-mails qui ont déjà un compte ou déjà un code en attente.
                    </p>
                </aside>
            </div>
        </main>
    </body>
</html>
