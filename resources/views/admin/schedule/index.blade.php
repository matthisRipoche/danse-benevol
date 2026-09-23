<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Jours et créneaux — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        @php
            $timeInput = 'h-10 w-full rounded-lg border border-sand-200 bg-white px-2 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none';
        @endphp

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-4">
                <h1 class="font-heading text-2xl font-bold">Missions</h1>
                <p class="text-sm text-stone-500">{{ $edition->name }} · du {{ $edition->start_date->format('d/m/Y') }} au {{ $edition->end_date->format('d/m/Y') }}</p>
            </div>

            @include('admin.missions.partials.tabs')
            @include('admin.partials.flash')

            <p class="mb-6 max-w-3xl text-sm text-stone-600">
                Chaque créneau ajouté s'ouvre automatiquement pour les {{ $missionCount }} mission(s) existante(s), avec leur capacité par défaut.
                Un jour ou un créneau sur lequel des bénévoles sont inscrits ne peut pas être supprimé.
            </p>

            <form method="POST" action="{{ route('admin.schedule.days.store') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-sand-200 bg-white p-4">
                @csrf
                <div class="flex flex-col gap-1">
                    <label for="date" class="text-sm font-medium text-stone-600">Ajouter un jour</label>
                    <input type="date" name="date" id="date" required value="{{ old('date') }}"
                        min="{{ $edition->start_date->format('Y-m-d') }}" max="{{ $edition->end_date->format('Y-m-d') }}"
                        class="h-10 rounded-lg border border-sand-200 bg-white px-3 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error('date') border-red-400 @enderror">
                </div>
                <button type="submit" class="h-10 rounded-full bg-brand-500 px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                    Ajouter le jour
                </button>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($days as $day)
                    @php
                        $dayBookings = $day->timeSlots->sum('volunteer_assignments_count');
                    @endphp
                    <section class="flex flex-col rounded-xl border border-sand-200 bg-white p-4">
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <h2 class="font-heading text-lg font-bold text-brand-500">
                                {{ $day->label }} <span class="block text-sm font-semibold text-stone-500">{{ $day->date->format('d/m/Y') }}</span>
                            </h2>
                            @if ($dayBookings === 0)
                                <form method="POST" action="{{ route('admin.schedule.days.destroy', $day) }}"
                                    onsubmit="return confirm('Supprimer le {{ $day->label }} {{ $day->date->format('d/m/Y') }} et ses créneaux ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Supprimer le jour</button>
                                </form>
                            @endif
                        </div>

                        <ul class="mb-3 flex flex-col divide-y divide-sand-100 rounded-lg border border-sand-100">
                            @forelse ($day->timeSlots as $timeSlot)
                                <li class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                                    <span class="font-mono font-semibold">{{ substr($timeSlot->starts_at, 0, 5) }}–{{ substr($timeSlot->ends_at, 0, 5) }}</span>
                                    <span class="flex items-center gap-2">
                                        <span class="text-xs text-stone-500">{{ $timeSlot->volunteer_assignments_count }} inscrit(s)</span>
                                        @if ($timeSlot->volunteer_assignments_count === 0)
                                            <form method="POST" action="{{ route('admin.schedule.time-slots.destroy', $timeSlot) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" aria-label="Supprimer le créneau {{ substr($timeSlot->starts_at, 0, 5) }}–{{ substr($timeSlot->ends_at, 0, 5) }}"
                                                    class="rounded-full p-1 text-stone-400 hover:bg-red-50 hover:text-red-700">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </span>
                                </li>
                            @empty
                                <li class="px-3 py-2 text-sm text-stone-500">Aucun créneau.</li>
                            @endforelse
                        </ul>

                        <form method="POST" action="{{ route('admin.schedule.time-slots.store', $day) }}" class="mt-auto flex items-end gap-2">
                            @csrf
                            <div class="flex flex-1 flex-col gap-1">
                                <label for="starts-{{ $day->id }}" class="text-xs font-medium text-stone-500">Début</label>
                                <input type="time" name="starts_at" id="starts-{{ $day->id }}" required class="{{ $timeInput }}">
                            </div>
                            <div class="flex flex-1 flex-col gap-1">
                                <label for="ends-{{ $day->id }}" class="text-xs font-medium text-stone-500">Fin</label>
                                <input type="time" name="ends_at" id="ends-{{ $day->id }}" required class="{{ $timeInput }}">
                            </div>
                            <button type="submit" class="h-10 shrink-0 rounded-full bg-white px-3 text-sm font-semibold text-stone-700 ring-1 ring-sand-200 hover:bg-sand-100">
                                Ajouter
                            </button>
                        </form>
                    </section>
                @empty
                    <p class="rounded-xl border border-dashed border-sand-200 bg-white p-6 text-sm text-stone-500 md:col-span-2 lg:col-span-3">
                        Aucun jour pour l'instant : ajoute les jours de l'édition, puis leurs créneaux horaires.
                    </p>
                @endforelse
            </div>
        </main>
    </body>
</html>
