<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $mission->name }} — {{ config('app.name', 'Laravel') }}</title>

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

            <h1 class="mb-5 font-heading text-2xl font-bold">{{ $mission->name }}</h1>

            @include('admin.partials.flash')

            <form method="POST" action="{{ route('admin.missions.update', $mission) }}" class="grid grid-cols-1 items-start gap-6 lg:grid-cols-5">
                @csrf
                @method('PUT')

                <section class="flex flex-col gap-5 rounded-xl border border-sand-200 bg-white p-6 lg:col-span-2">
                    @include('admin.missions.partials.fields', [
                        'defaultCapacityHint' => 'Utilisé pour les créneaux ajoutés plus tard. Ne modifie pas les créneaux existants : ajuste-les dans la grille.',
                    ])
                </section>

                <section class="rounded-xl border border-sand-200 bg-white p-6 lg:col-span-3">
                    <h2 class="font-heading text-lg font-semibold">Places par créneau</h2>
                    <p class="mb-4 text-sm text-stone-500">0 = mission fermée sur ce créneau. Impossible de descendre sous le nombre de bénévoles déjà inscrits.</p>

                    @forelse ($days as $day)
                        <div class="mb-5 last:mb-0">
                            <h3 class="mb-2 text-sm font-bold tracking-wider text-brand-500 uppercase">
                                {{ $day->label }} <span class="font-semibold text-stone-500">{{ $day->date->format('d/m/Y') }}</span>
                            </h3>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($day->timeSlots as $timeSlot)
                                    @php
                                        $missionSlot = $missionSlots->get($timeSlot->id);
                                        $booked = $missionSlot?->volunteer_assignments_count ?? 0;
                                    @endphp
                                    <label for="capacity-{{ $timeSlot->id }}" class="flex flex-col gap-1 rounded-lg bg-sand-50 p-3">
                                        <span class="font-mono text-xs font-semibold text-stone-600">{{ substr($timeSlot->starts_at, 0, 5) }}–{{ substr($timeSlot->ends_at, 0, 5) }}</span>
                                        <input type="number" name="capacities[{{ $timeSlot->id }}]" id="capacity-{{ $timeSlot->id }}" min="{{ $booked }}" max="500"
                                            value="{{ old("capacities.{$timeSlot->id}", $missionSlot?->capacity ?? 0) }}"
                                            class="h-10 w-full rounded-lg border border-sand-200 bg-white px-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error("capacities.{$timeSlot->id}") border-red-400 @enderror">
                                        <span class="text-xs text-stone-500">{{ $booked }} inscrit(s)</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg bg-sand-50 p-4 text-sm text-stone-500">
                            Aucun créneau horaire pour l'instant.
                            <a href="{{ route('admin.schedule.index') }}" class="font-semibold text-brand-500 hover:underline">Créer les jours et créneaux</a>
                        </p>
                    @endforelse
                </section>

                <div class="lg:col-span-5">
                    <button type="submit" class="h-11 w-full rounded-full bg-brand-500 px-6 font-semibold text-white shadow-sm hover:bg-brand-600 sm:w-fit">
                        Enregistrer
                    </button>
                </div>
            </form>
        </main>
    </body>
</html>
