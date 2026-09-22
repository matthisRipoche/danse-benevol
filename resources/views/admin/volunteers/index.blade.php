<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Bénévoles — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="mx-auto max-w-6xl p-4 sm:p-6 lg:p-8">
            @include('admin.partials.nav')

            <h1 class="mb-6 text-lg font-semibold">Bénévoles — {{ $edition->name }}</h1>

            <form method="GET" action="{{ route('admin.volunteers.index') }}" class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <input type="text" name="prenom" value="{{ request('prenom') }}" placeholder="Prénom"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900">

                <input type="text" name="nom" value="{{ request('nom') }}" placeholder="Nom"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900">

                <select name="statut" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">Tous les statuts</option>
                    <option value="valide" @selected(request('statut') === 'valide')>Validé</option>
                    <option value="attente" @selected(request('statut') === 'attente')>En attente</option>
                </select>

                <select name="mission" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">Toutes les missions</option>
                    @foreach ($missions as $mission)
                        <option value="{{ $mission->id }}" @selected((int) request('mission') === $mission->id)>{{ $mission->name }}</option>
                    @endforeach
                </select>

                <select name="jour" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">Tous les jours</option>
                    @foreach ($days as $day)
                        <option value="{{ $day->id }}" @selected((int) request('jour') === $day->id)>{{ $day->label }} ({{ $day->date->format('d/m') }})</option>
                    @endforeach
                </select>

                <div class="col-span-2 flex gap-2 sm:col-span-3 lg:col-span-5">
                    <button type="submit"
                        class="rounded-md bg-brand-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-600">
                        Rechercher
                    </button>
                    <a href="{{ route('admin.volunteers.index') }}" class="px-4 py-1.5 text-sm underline">
                        Réinitialiser
                    </a>
                </div>
            </form>

            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-300 dark:border-gray-600">
                        <th class="py-2 pr-4">Prénom</th>
                        <th class="py-2 pr-4">Nom</th>
                        <th class="py-2 pr-4">E-mail</th>
                        <th class="py-2 pr-4">Mineur</th>
                        <th class="py-2 pr-4">Statut</th>
                        <th class="py-2">Missions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($volunteers as $volunteer)
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <td class="py-2 pr-4">{{ $volunteer->first_name }}</td>
                            <td class="py-2 pr-4">{{ $volunteer->last_name }}</td>
                            <td class="py-2 pr-4">{{ $volunteer->email }}</td>
                            <td class="py-2 pr-4">{{ $volunteer->is_minor ? 'Oui' : 'Non' }}</td>
                            <td class="py-2 pr-4">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $volunteer->pivot->is_validated,
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' => ! $volunteer->pivot->is_validated,
                                ])>
                                    {{ $volunteer->pivot->is_validated ? 'validé' : 'en attente' }}
                                </span>
                            </td>
                            <td class="py-2">
                                {{ $volunteer->volunteerAssignments->pluck('missionSlot.mission.name')->unique()->join(', ') ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-gray-500 dark:text-gray-400">
                                Aucun bénévole ne correspond à ces critères.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">
                {{ $volunteers->links() }}
            </div>
        </div>
    </body>
</html>
