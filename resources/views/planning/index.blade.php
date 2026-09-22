<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Mon planning — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="mx-auto max-w-2xl p-4 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-lg font-semibold">Mon planning — {{ $edition->name }}</h1>
                @include('partials.logout-button')
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-md border border-green-300 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            @if ($isValidated)
                <div class="mb-4 rounded-md border border-blue-300 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300">
                    Ton planning est validé et verrouillé. Seul un administrateur peut le modifier désormais.
                </div>
            @endif

            <div class="mb-6 rounded-md border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
                <p>Choisis entre {{ $edition->min_slots_per_volunteer }} et {{ $edition->max_slots_per_volunteer }} créneaux sur le week-end, sans dépasser {{ $edition->max_consecutive_slots }} créneaux consécutifs le même jour. Tu ne vois que le nombre de places restantes, jamais qui d'autre est inscrit.</p>
            </div>

            @foreach ($days as $day)
                <section class="mb-8">
                    <h2 class="mb-2 font-semibold">{{ $day->label }} — {{ $day->date->format('d/m/Y') }}</h2>

                    @foreach ($day->timeSlots as $timeSlot)
                        @php
                            $takenHere = in_array($timeSlot->id, $reservedTimeSlotIds);
                        @endphp
                        <div class="mb-3 rounded-md border border-gray-200 p-3 dark:border-gray-700">
                            <p class="mb-2 text-sm font-medium">{{ substr($timeSlot->starts_at, 0, 5) }} – {{ substr($timeSlot->ends_at, 0, 5) }}</p>

                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($timeSlot->missionSlots as $missionSlot)
                                    @php
                                        $isMine = in_array($missionSlot->id, $reservedMissionSlotIds);
                                        $gauge = $missionSlot->gaugeStatus();
                                    @endphp
                                    <div class="flex items-center justify-between rounded-md border border-gray-200 p-3 text-sm dark:border-gray-700">
                                        <div>
                                            <p class="font-medium">{{ $missionSlot->mission->name }}</p>
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $gauge === 'disponible',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' => $gauge === 'presque-complet',
                                                'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => $gauge === 'complet',
                                            ])>
                                                {{ $missionSlot->remainingCapacity() }} place(s) restante(s)
                                            </span>
                                        </div>

                                        @unless ($isValidated)
                                            @if ($isMine)
                                                <form method="POST" action="{{ route('planning.cancel', $missionSlot) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm text-red-600 hover:underline dark:text-red-400">
                                                        Annuler
                                                    </button>
                                                </form>
                                            @elseif ($takenHere)
                                                <span class="text-xs text-gray-400">Créneau pris</span>
                                            @elseif ($gauge === 'complet')
                                                <span class="text-xs text-gray-400">Complet</span>
                                            @else
                                                <form method="POST" action="{{ route('planning.reserve', $missionSlot) }}">
                                                    @csrf
                                                    <button type="submit" class="text-sm font-medium underline">
                                                        Réserver
                                                    </button>
                                                </form>
                                            @endif
                                        @endunless
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>
            @endforeach

            <section class="mt-8 rounded-md border border-gray-300 p-4 dark:border-gray-600">
                <h2 class="mb-2 font-semibold">Récapitulatif</h2>

                <ul class="mb-4 list-inside list-disc text-sm">
                    @forelse ($assignments as $assignment)
                        <li>
                            {{ $assignment->missionSlot->timeSlot->eventDay->label }}
                            {{ substr($assignment->missionSlot->timeSlot->starts_at, 0, 5) }}–{{ substr($assignment->missionSlot->timeSlot->ends_at, 0, 5) }}
                            — {{ $assignment->missionSlot->mission->name }}
                        </li>
                    @empty
                        <li class="list-none text-gray-500 dark:text-gray-400">Aucun créneau réservé pour l'instant.</li>
                    @endforelse
                </ul>

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ $assignments->count() }} / {{ $edition->max_slots_per_volunteer }} créneaux réservés.
                </p>

                @unless ($isValidated)
                    <form method="POST" action="{{ route('planning.finalize') }}"
                        onsubmit="return confirm('Confirmer la validation définitive de ton planning ? Il sera ensuite verrouillé et modifiable uniquement par un administrateur.');">
                        @csrf
                        <button type="submit"
                            class="rounded-md bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white hover:bg-black dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                            Valider définitivement
                        </button>
                    </form>
                @endunless
            </section>
        </div>
    </body>
</html>
