<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Postes restreints — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-6">
                <h1 class="font-heading text-2xl font-bold">Postes restreints</h1>
                <p class="text-sm text-stone-500">Missions non visibles des bénévoles, assignées manuellement · {{ $edition->name }}</p>
            </div>

            @include('admin.partials.flash')

            <div class="flex flex-col gap-8">
                @foreach ($days as $day)
                    <section>
                        <h2 class="mb-3 font-heading text-lg font-bold text-brand-500">
                            {{ $day->label }} <span class="text-sm font-semibold text-stone-500">{{ $day->date->format('d/m/Y') }}</span>
                        </h2>

                        <div class="overflow-hidden rounded-xl border border-sand-200 bg-white">
                            @foreach ($day->timeSlots as $timeSlot)
                                <div class="grid grid-cols-1 gap-3 border-b border-sand-100 p-4 last:border-b-0 lg:grid-cols-[7rem_1fr]">
                                    <p class="font-semibold text-stone-600">{{ substr($timeSlot->starts_at, 0, 5) }} – {{ substr($timeSlot->ends_at, 0, 5) }}</p>

                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        @foreach ($timeSlot->missionSlots as $missionSlot)
                                            @php
                                                $assignedCount = $missionSlot->volunteerAssignments->count();
                                            @endphp
                                            <div class="rounded-lg bg-sand-50 p-3">
                                                <div class="mb-2 flex items-center justify-between gap-2">
                                                    <p class="font-semibold">{{ $missionSlot->mission->name }}</p>
                                                    <span @class([
                                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                                        'bg-emerald-100 text-emerald-800' => $assignedCount >= $missionSlot->capacity,
                                                        'bg-white text-stone-600' => $assignedCount < $missionSlot->capacity,
                                                    ])>
                                                        {{ $assignedCount }}/{{ $missionSlot->capacity }}
                                                    </span>
                                                </div>

                                                <ul class="mb-2 flex flex-col text-sm">
                                                    @forelse ($missionSlot->volunteerAssignments as $assignment)
                                                        <li class="flex items-center justify-between gap-2 py-1">
                                                            <span class="min-w-0 truncate">{{ $assignment->user->first_name }} {{ $assignment->user->last_name }}</span>
                                                            <form method="POST" action="{{ route('admin.restricted-missions.unassign', $assignment) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="rounded-full px-2 py-0.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                                    Retirer
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @empty
                                                        <li class="py-1 text-stone-500">Personne assigné(e) pour l'instant.</li>
                                                    @endforelse
                                                </ul>

                                                @if ($missionSlot->remainingCapacity() > 0)
                                                    <form method="POST" action="{{ route('admin.restricted-missions.assign', $missionSlot) }}" class="flex gap-2">
                                                        @csrf
                                                        <input type="email" name="email" placeholder="E-mail du bénévole" aria-label="E-mail du bénévole à assigner" required
                                                            class="h-9 min-w-0 flex-1 rounded-lg border border-sand-200 bg-white px-3 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none">
                                                        <button type="submit" class="h-9 shrink-0 rounded-full bg-brand-500 px-3.5 text-sm font-semibold text-white hover:bg-brand-600">
                                                            Assigner
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </main>
    </body>
</html>
