<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Bénévoles — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        @php
            $field = 'h-10 w-full rounded-lg border border-sand-200 bg-white px-3 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none';
        @endphp

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-6 flex flex-wrap items-baseline justify-between gap-2">
                <h1 class="font-heading text-2xl font-bold">Bénévoles</h1>
                <p class="text-sm text-stone-500">{{ $volunteers->total() }} résultat(s) · {{ $edition->name }}</p>
            </div>

            @include('admin.partials.flash')

            <form method="GET" action="{{ route('admin.volunteers.index') }}" class="mb-6 grid grid-cols-2 gap-2 rounded-xl border border-sand-200 bg-white p-3 sm:grid-cols-3 lg:grid-cols-7">
                <input type="text" name="prenom" value="{{ request('prenom') }}" placeholder="Prénom" aria-label="Prénom" class="{{ $field }}">
                <input type="text" name="nom" value="{{ request('nom') }}" placeholder="Nom" aria-label="Nom" class="{{ $field }}">

                <select name="statut" aria-label="Statut" class="{{ $field }}">
                    <option value="">Tous les statuts</option>
                    <option value="valide" @selected(request('statut') === 'valide')>Validé</option>
                    <option value="attente" @selected(request('statut') === 'attente')>En attente</option>
                </select>

                <select name="mission" aria-label="Mission" class="{{ $field }}">
                    <option value="">Toutes les missions</option>
                    @foreach ($missions as $mission)
                        <option value="{{ $mission->id }}" @selected((int) request('mission') === $mission->id)>{{ $mission->name }}</option>
                    @endforeach
                </select>

                <select name="jour" aria-label="Jour" class="{{ $field }}">
                    <option value="">Tous les jours</option>
                    @foreach ($days as $day)
                        <option value="{{ $day->id }}" @selected((int) request('jour') === $day->id)>{{ $day->label }} ({{ $day->date->format('d/m') }})</option>
                    @endforeach
                </select>

                <select name="mineur" aria-label="Profil mineur" class="{{ $field }}">
                    <option value="">Mineurs : tous</option>
                    <option value="a_valider" @selected(request('mineur') === 'a_valider')>Mineurs à valider</option>
                    <option value="valide" @selected(request('mineur') === 'valide')>Mineurs validés</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="h-10 flex-1 rounded-full bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                        Rechercher
                    </button>
                    <a href="{{ route('admin.volunteers.index') }}" title="Réinitialiser" aria-label="Réinitialiser"
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-sand-100 text-stone-600 hover:bg-sand-200">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg>
                    </a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-sand-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-sand-100/60 text-xs tracking-wider text-stone-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Bénévole</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                            <th class="hidden px-4 py-3 font-semibold md:table-cell">Planning</th>
                            <th class="px-4 py-3"><span class="sr-only">Détail</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-100">
                        @forelse ($volunteers as $volunteer)
                            @php
                                $sortedAssignments = $volunteer->volunteerAssignments->sortBy(fn ($assignment) => [
                                    $assignment->missionSlot->timeSlot->eventDay->date,
                                    $assignment->missionSlot->timeSlot->starts_at,
                                ]);
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">
                                        <a href="{{ route('admin.volunteers.show', $volunteer) }}" class="hover:text-brand-500 hover:underline">{{ $volunteer->first_name }} {{ $volunteer->last_name }}</a>
                                    </p>
                                    <p class="break-all text-stone-500">{{ $volunteer->email }}</p>
                                    @if ($volunteer->is_minor)
                                        <div class="mt-1.5">
                                            @include('admin.volunteers.partials.minor-status')
                                        </div>
                                    @endif
                                    <div class="mt-2 md:hidden">
                                        @include('admin.volunteers.partials.schedule', ['assignments' => $sortedAssignments])
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold whitespace-nowrap',
                                        'bg-emerald-100 text-emerald-800' => $volunteer->pivot->is_validated,
                                        'bg-sand-100 text-stone-600' => ! $volunteer->pivot->is_validated,
                                    ])>
                                        {{ $volunteer->pivot->is_validated ? 'Validé' : 'En attente' }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 md:table-cell">
                                    @include('admin.volunteers.partials.schedule', ['assignments' => $sortedAssignments])
                                </td>
                                <td class="px-2 py-3 text-right">
                                    <a href="{{ route('admin.volunteers.show', $volunteer) }}" aria-label="Voir le détail de {{ $volunteer->first_name }} {{ $volunteer->last_name }}"
                                        class="inline-flex size-8 items-center justify-center rounded-full text-stone-400 hover:bg-sand-100 hover:text-brand-500">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-stone-500">
                                    Aucun bénévole ne correspond à ces critères.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $volunteers->links() }}
            </div>
        </main>
    </body>
</html>
