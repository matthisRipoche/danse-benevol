<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Missions — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="font-heading text-2xl font-bold">Missions</h1>
                    <p class="text-sm text-stone-500">{{ $missions->count() }} mission(s) · {{ $edition->name }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.missions.import') }}"
                        class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm ring-1 ring-sand-200 hover:bg-sand-100">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                        Importer
                    </a>
                    <a href="{{ route('admin.missions.create') }}"
                        class="inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Nouvelle mission
                    </a>
                </div>
            </div>

            @include('admin.missions.partials.tabs')
            @include('admin.partials.flash')
            @include('admin.partials.import-report')

            @if ($timeSlotCount === 0)
                <p class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    L'édition n'a encore aucun créneau horaire : les missions ne sont réservables sur aucun créneau.
                    <a href="{{ route('admin.schedule.index') }}" class="font-semibold underline">Créer les jours et créneaux</a>
                </p>
            @endif

            <div class="overflow-hidden rounded-xl border border-sand-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-sand-100/60 text-xs tracking-wider text-stone-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Mission</th>
                            <th class="hidden px-4 py-3 font-semibold sm:table-cell">Places par créneau</th>
                            <th class="hidden px-4 py-3 font-semibold md:table-cell">Créneaux ouverts</th>
                            <th class="px-4 py-3 font-semibold">Inscrits</th>
                            <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-100">
                        @forelse ($missions as $mission)
                            @php
                                $capacities = $mission->missionSlots->pluck('capacity');
                                $bookedCount = $mission->missionSlots->sum('volunteer_assignments_count');
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">
                                        <a href="{{ route('admin.missions.edit', $mission) }}" class="hover:text-brand-500 hover:underline">{{ $mission->name }}</a>
                                        @unless ($mission->is_public)
                                            <span class="ml-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-500">Restreinte</span>
                                        @endunless
                                        @if ($mission->is_adult_only)
                                            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Interdite aux mineurs</span>
                                        @endif
                                    </p>
                                    @if ($mission->description)
                                        <p class="line-clamp-2 text-stone-500">{{ $mission->description }}</p>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-stone-600 sm:table-cell">
                                    @if ($capacities->isEmpty())
                                        —
                                    @elseif ($capacities->min() === $capacities->max())
                                        {{ $capacities->min() }}
                                    @else
                                        {{ $capacities->min() }} à {{ $capacities->max() }}
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-stone-600 md:table-cell">{{ $capacities->count() }} / {{ $timeSlotCount }}</td>
                                <td class="px-4 py-3 text-stone-600">{{ $bookedCount }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.missions.edit', $mission) }}" class="rounded-full px-3 py-1 text-xs font-semibold text-stone-700 ring-1 ring-sand-200 hover:bg-sand-100">
                                            Modifier
                                        </a>
                                        <form method="POST" action="{{ route('admin.missions.destroy', $mission) }}"
                                            onsubmit="return confirm('Supprimer la mission « {{ addslashes($mission->name) }} » ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-full px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200 hover:bg-red-50">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-stone-500">
                                    Aucune mission pour l'instant. Crée-en une ou importe un fichier.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </body>
</html>
