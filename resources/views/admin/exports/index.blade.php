<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Exports — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-6">
                <h1 class="font-heading text-2xl font-bold">Exports</h1>
                <p class="text-sm text-stone-500">Données de l'édition {{ $edition->name }}, générées au moment du téléchargement.</p>
            </div>

            <div class="overflow-hidden rounded-xl border border-sand-200 bg-white">
                <ul class="divide-y divide-sand-100">
                    @foreach ($exports as $type => $export)
                        <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <h2 class="font-heading font-semibold">{{ $export['label'] }}</h2>
                                <p class="text-sm text-stone-500">{{ $export['description'] }}</p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <a href="{{ route('admin.exports.download', [$type, 'xlsx']) }}"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                    Excel
                                </a>
                                <a href="{{ route('admin.exports.download', [$type, 'csv']) }}"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm ring-1 ring-sand-200 hover:bg-sand-100">
                                    CSV
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="mt-4 text-sm text-stone-500">Ces fichiers contiennent des données personnelles (dont des mineurs) : chaque téléchargement est enregistré dans le journal d'audit. Ne les partage qu'avec les personnes concernées et supprime-les après usage.</p>
        </main>
    </body>
</html>
