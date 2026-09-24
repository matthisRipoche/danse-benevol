<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Éditions — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        @php
            $statusLabels = ['draft' => 'Brouillon', 'active' => 'Active', 'archived' => 'Archivée'];
        @endphp

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="font-heading text-2xl font-bold">Éditions</h1>
                    <p class="text-sm text-stone-500">Toute la plateforme travaille sur l'édition active : planning des bénévoles, missions, codes et exports.</p>
                </div>
                <a href="{{ route('admin.editions.create') }}"
                    class="inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Nouvelle édition
                </a>
            </div>

            @include('admin.partials.flash')

            <div class="overflow-hidden rounded-xl border border-sand-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-sand-100/60 text-xs tracking-wider text-stone-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Édition</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                            <th class="hidden px-4 py-3 font-semibold md:table-cell">Jours</th>
                            <th class="hidden px-4 py-3 font-semibold md:table-cell">Missions</th>
                            <th class="hidden px-4 py-3 font-semibold sm:table-cell">Bénévoles</th>
                            <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-100">
                        @forelse ($editions as $listedEdition)
                            <tr @class(['bg-brand-50/40' => $listedEdition->status === 'active'])>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ $listedEdition->name }}</p>
                                    <p class="text-stone-500">du {{ $listedEdition->start_date->format('d/m/Y') }} au {{ $listedEdition->end_date->format('d/m/Y') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' => $listedEdition->status === 'active',
                                        'bg-amber-100 text-amber-800' => $listedEdition->status === 'draft',
                                        'bg-sand-100 text-stone-600' => $listedEdition->status === 'archived',
                                    ])>{{ $statusLabels[$listedEdition->status] ?? $listedEdition->status }}</span>
                                </td>
                                <td class="hidden px-4 py-3 text-stone-600 md:table-cell">{{ $listedEdition->event_days_count }}</td>
                                <td class="hidden px-4 py-3 text-stone-600 md:table-cell">{{ $listedEdition->missions_count }}</td>
                                <td class="hidden px-4 py-3 text-stone-600 sm:table-cell">{{ $listedEdition->volunteers_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-1">
                                        <a href="{{ route('admin.editions.edit', $listedEdition) }}" class="rounded-full px-3 py-1 text-xs font-semibold text-stone-700 ring-1 ring-sand-200 hover:bg-sand-100">
                                            Modifier
                                        </a>
                                        @unless ($listedEdition->status === 'active')
                                            <form method="POST" action="{{ route('admin.editions.activate', $listedEdition) }}"
                                                onsubmit="return confirm('Activer « {{ addslashes($listedEdition->name) }} » ? L\'édition active actuelle sera archivée et ses codes d\'invitation en attente révoqués.');">
                                                @csrf
                                                <button type="submit" class="rounded-full bg-brand-500 px-3 py-1 text-xs font-semibold text-white hover:bg-brand-600">
                                                    Activer
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-500">Aucune édition pour l'instant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6 rounded-xl border border-sand-200 bg-white p-5 text-sm text-stone-600">
                <h2 class="mb-2 font-heading text-base font-semibold text-[#1e1e24]">Préparer une nouvelle édition</h2>
                <ol class="list-inside list-decimal space-y-1">
                    <li>Crée l'édition, en reprenant si besoin la grille de l'année précédente.</li>
                    <li>Active-la quand l'inscription des bénévoles doit commencer : l'ancienne passe en archive.</li>
                    <li>Ajuste les missions, jours et créneaux, puis envoie les codes d'invitation.</li>
                </ol>
            </div>
        </main>
    </body>
</html>
