<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Tableau de bord — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="mx-auto max-w-3xl p-6">
            @include('admin.partials.nav')

            <h1 class="mb-6 text-lg font-semibold">Tableau de bord — {{ $edition->name }}</h1>

            <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-2xl font-semibold">{{ $totalInvitationCodes }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Codes émis</p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        {{ $invitationCodeCounts->get('pending', 0) }} en attente
                        · {{ $invitationCodeCounts->get('used', 0) }} utilisés
                        · {{ $invitationCodeCounts->get('revoked', 0) }} révoqués
                    </p>
                </div>

                <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-2xl font-semibold">{{ $totalVolunteers }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Comptes créés</p>
                </div>

                <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-2xl font-semibold">{{ $validatedVolunteers }}/{{ $totalVolunteers }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Plannings validés</p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $pendingVolunteers }} en attente</p>
                </div>

                <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-2xl font-semibold">{{ $globalFillRate }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Taux de remplissage</p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $totalFilled }}/{{ $totalCapacity }} places</p>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="mb-2 font-semibold">Remplissage par jour</h2>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <th class="py-2 pr-4">Jour</th>
                            <th class="py-2 pr-4">Places occupées</th>
                            <th class="py-2">Taux</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fillRateByDay as $row)
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <td class="py-2 pr-4">{{ $row->label }}</td>
                                <td class="py-2 pr-4">{{ $row->filled }}/{{ $row->capacity }}</td>
                                <td class="py-2">{{ $row->rate }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <h2 class="mb-2 font-semibold">Remplissage par mission</h2>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <th class="py-2 pr-4">Mission</th>
                            <th class="py-2 pr-4">Places occupées</th>
                            <th class="py-2">Taux</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fillRateByMission as $row)
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <td class="py-2 pr-4">{{ $row->label }}</td>
                                <td class="py-2 pr-4">{{ $row->filled }}/{{ $row->capacity }}</td>
                                <td class="py-2">{{ $row->rate }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
