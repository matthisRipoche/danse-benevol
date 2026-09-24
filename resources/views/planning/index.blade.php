<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Mon planning — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 pb-24 text-[#1e1e24] antialiased lg:pb-0">
        @php
            $reservedCount = $assignments->count();
            $maxSlots = $edition->max_slots_per_volunteer;
            $minSlots = $edition->min_slots_per_volunteer;
            $isReadOnly = $isValidated || $registrationClosedMessage !== null;
            $statusTitle = match (true) {
                $isValidated => 'Planning validé',
                $isReadOnly => 'Lecture seule',
                default => 'Brouillon modifiable',
            };
        @endphp

        @include('partials.volunteer-header')

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <section class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-sand-100 via-sand-50 to-sand-200 p-5 shadow-sm sm:p-8">
                <div class="pointer-events-none absolute -top-16 -right-16 size-80 rounded-full bg-brand-500/5 blur-3xl"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold tracking-wider text-brand-500 uppercase">
                            <span class="size-2 rounded-full bg-brand-500"></span>
                            {{ $edition->name }}
                            @if ($edition->start_date && $edition->end_date)
                                • {{ $edition->start_date->format('d/m') }} – {{ $edition->end_date->format('d/m/Y') }}
                            @endif
                        </span>
                        <h1 class="font-heading text-2xl font-bold text-brand-500 sm:text-3xl">Mon planning bénévole</h1>
                        <p class="mt-1 text-stone-600">
                            Choisis entre {{ $minSlots }} et {{ $maxSlots }} créneaux sur le week-end, sans dépasser {{ $edition->max_consecutive_slots }} créneaux consécutifs le même jour.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-xl border border-sand-200 bg-white p-4 shadow-sm lg:w-80">
                        <div @class([
                            'flex size-10 shrink-0 items-center justify-center rounded-full',
                            'bg-emerald-100 text-emerald-700' => $isValidated,
                            'bg-amber-100 text-amber-700' => ! $isValidated,
                        ])>
                            @if ($isValidated)
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                            @else
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $statusTitle }}</p>
                            <p class="text-sm text-stone-500">
                                @if ($isValidated)
                                    Seul un administrateur peut le modifier.
                                @elseif ($isReadOnly)
                                    Les inscriptions ne sont pas ouvertes en ce moment.
                                @else
                                    Libre à toi de changer avant de valider.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="relative mt-6 border-t border-sand-200 pt-5">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-semibold">Ma sélection</span>
                        <span class="font-heading font-bold text-brand-500">{{ $reservedCount }} / {{ $maxSlots }} créneaux</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-white">
                        <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $maxSlots > 0 ? min(100, $reservedCount / $maxSlots * 100) : 0 }}%"></div>
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-stone-500">
                        <span>Min. {{ $minSlots }}</span>
                        <span class="hidden text-center sm:inline">Tu ne vois que les places restantes, jamais qui est inscrit.</span>
                        <span>Max. {{ $maxSlots }}</span>
                    </div>
                </div>
            </section>

            @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($registrationClosedMessage && ! $isValidated && session('error') !== $registrationClosedMessage)
                <div class="mb-6 flex items-start gap-2.5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <p>{{ $registrationClosedMessage }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                <div class="flex min-w-0 flex-col gap-6 lg:col-span-8">
                    <nav class="sticky top-16 z-20 -mx-4 flex gap-2 overflow-x-auto bg-sand-50/95 px-4 py-2 backdrop-blur sm:mx-0 sm:rounded-full sm:px-2" aria-label="Jours">
                        @foreach ($days as $day)
                            @php
                                $dayCount = $assignments->where('missionSlot.timeSlot.event_day_id', $day->id)->count();
                            @endphp
                            <a href="#jour-{{ $day->id }}" class="flex shrink-0 items-center gap-2 rounded-full border border-sand-200 bg-white px-4 py-2 text-sm font-semibold whitespace-nowrap shadow-sm hover:border-brand-500 hover:text-brand-500">
                                {{ $day->label }}
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs',
                                    'bg-brand-500 text-white' => $dayCount > 0,
                                    'bg-sand-100 text-stone-500' => $dayCount === 0,
                                ])>{{ $dayCount }}</span>
                            </a>
                        @endforeach
                    </nav>

                    @foreach ($days as $day)
                        <section id="jour-{{ $day->id }}" class="flex scroll-mt-32 flex-col gap-4">
                            <h2 class="font-heading text-xl font-bold text-brand-500">
                                {{ $day->label }}
                                <span class="text-base font-semibold text-stone-500">{{ $day->date->format('d/m/Y') }}</span>
                            </h2>

                            @foreach ($day->timeSlots as $timeSlot)
                                @php
                                    $takenHere = in_array($timeSlot->id, $reservedTimeSlotIds);
                                @endphp
                                <div class="rounded-2xl border border-sand-200 bg-white p-4 shadow-sm sm:p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-sand-100 pb-3">
                                        <div class="flex items-center gap-2">
                                            <svg class="size-5 text-sand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                            <h3 class="font-heading text-lg font-semibold">{{ substr($timeSlot->starts_at, 0, 5) }} – {{ substr($timeSlot->ends_at, 0, 5) }}</h3>
                                        </div>
                                        @if ($takenHere)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                <span class="size-1.5 rounded-full bg-emerald-600"></span>
                                                Créneau réservé
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        @foreach ($timeSlot->missionSlots as $missionSlot)
                                            @php
                                                $isMine = in_array($missionSlot->id, $reservedMissionSlotIds);
                                                $gauge = $missionSlot->gaugeStatus();
                                                $remaining = $missionSlot->remainingCapacity();
                                                $isForbiddenToMe = $isMinor && $missionSlot->mission->is_adult_only;
                                            @endphp
                                            <div @class([
                                                'flex flex-col justify-between gap-3 rounded-xl p-4 transition',
                                                'bg-brand-50 ring-2 ring-brand-500' => $isMine,
                                                'bg-sand-50 opacity-60' => ! $isMine && ($gauge === 'complet' || $takenHere || $isForbiddenToMe),
                                                'bg-sand-50 hover:bg-sand-100' => ! $isMine && $gauge !== 'complet' && ! $takenHere && ! $isForbiddenToMe,
                                            ])>
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        @if ($isMine)
                                                            <p class="mb-1 flex items-center gap-1 text-xs font-semibold tracking-wider text-brand-500 uppercase">
                                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                                Inscrit(e)
                                                            </p>
                                                        @endif
                                                        <p @class(['font-semibold', 'text-brand-600' => $isMine])>{{ $missionSlot->mission->name }}</p>
                                                        @if ($isForbiddenToMe)
                                                            <p class="mt-1 text-xs font-semibold text-amber-800">Interdite aux mineurs</p>
                                                        @endif
                                                        @if ($missionSlot->mission->description)
                                                            <p class="mt-0.5 text-sm text-stone-500">{{ $missionSlot->mission->description }}</p>
                                                        @endif
                                                    </div>
                                                    <span @class([
                                                        'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold',
                                                        'bg-emerald-100 text-emerald-800' => $gauge === 'disponible',
                                                        'bg-amber-100 text-amber-800' => $gauge === 'presque-complet',
                                                        'bg-red-100 text-red-800' => $gauge === 'complet',
                                                    ])>
                                                        {{ $gauge === 'complet' ? 'Complet' : $remaining.' '.($remaining > 1 ? 'places' : 'place') }}
                                                    </span>
                                                </div>

                                                @unless ($isReadOnly)
                                                    <div class="flex justify-end border-t border-sand-200/70 pt-3">
                                                        @if ($isMine)
                                                            <form method="POST" action="{{ route('planning.cancel', $missionSlot) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-brand-600 shadow-sm hover:bg-red-50 hover:text-red-700">
                                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                                                    Annuler
                                                                </button>
                                                            </form>
                                                        @elseif ($isForbiddenToMe)
                                                            <span class="py-1.5 text-sm text-stone-500 italic">Réservée aux majeurs</span>
                                                        @elseif ($takenHere)
                                                            <span class="py-1.5 text-sm text-stone-500 italic">Créneau pris</span>
                                                        @elseif ($gauge === 'complet')
                                                            <span class="py-1.5 text-sm text-stone-500 italic">Complet</span>
                                                        @else
                                                            <form method="POST" action="{{ route('planning.reserve', $missionSlot) }}">
                                                                @csrf
                                                                <button type="submit" class="inline-flex items-center gap-1 rounded-full bg-brand-500 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                                                    Réserver
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endunless
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </section>
                    @endforeach
                </div>

                <aside id="recapitulatif" class="flex scroll-mt-20 flex-col gap-4 lg:sticky lg:top-24 lg:col-span-4">
                    <div class="rounded-2xl border border-sand-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between border-b border-sand-100 pb-3">
                            <h2 class="font-heading text-lg font-bold text-brand-500">Mon récapitulatif</h2>
                            <span class="rounded-full bg-sand-100 px-2.5 py-0.5 text-sm font-bold text-brand-500">{{ $reservedCount }} / {{ $maxSlots }}</span>
                        </div>

                        <ul class="my-1 divide-y divide-sand-100">
                            @forelse ($assignments as $assignment)
                                <li class="flex items-start justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold tracking-wider text-stone-500 uppercase">{{ $assignment->missionSlot->timeSlot->eventDay->label }}</p>
                                        <p class="font-semibold">
                                            {{ substr($assignment->missionSlot->timeSlot->starts_at, 0, 5) }}–{{ substr($assignment->missionSlot->timeSlot->ends_at, 0, 5) }}
                                            <span class="text-stone-400">•</span>
                                            <span class="text-brand-500">{{ $assignment->missionSlot->mission->name }}</span>
                                        </p>
                                    </div>
                                    @unless ($isReadOnly)
                                        <form method="POST" action="{{ route('planning.cancel', $assignment->missionSlot) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Retirer ce créneau" aria-label="Retirer ce créneau"
                                                class="rounded-full p-1.5 text-stone-400 hover:bg-red-50 hover:text-red-700">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg>
                                            </button>
                                        </form>
                                    @endunless
                                </li>
                            @empty
                                <li class="py-4 text-sm text-stone-500">Aucun créneau réservé pour l'instant.</li>
                            @endforelse
                        </ul>

                        @if ($isValidated)
                            <div class="mt-2 flex items-start gap-2.5 rounded-xl bg-emerald-50 p-3.5 text-sm text-emerald-800">
                                <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                <p>Ton planning est validé et verrouillé. Seul un administrateur peut le modifier désormais.</p>
                            </div>
                        @elseif ($isReadOnly)
                            <p class="mt-2 rounded-xl bg-amber-50 p-3.5 text-sm text-amber-900">{{ $registrationClosedMessage }}</p>
                        @else
                            @if ($isAwaitingMinorValidation)
                                <p class="mt-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                                    Ton profil mineur doit d'abord être validé par l'organisation. Tu peux déjà réserver tes créneaux, la validation définitive sera possible ensuite.
                                </p>
                            @endif

                            @if ($reservedCount < $minSlots)
                                <p class="mt-2 rounded-xl bg-sand-100 p-3 text-sm text-stone-600">
                                    Encore <strong class="text-stone-900">{{ $minSlots - $reservedCount }} créneau(x)</strong> à réserver avant de pouvoir valider.
                                </p>
                            @elseif ($reservedCount < $maxSlots)
                                <p class="mt-2 rounded-xl bg-sand-100 p-3 text-sm text-stone-600">
                                    Tu peux encore ajouter <strong class="text-stone-900">{{ $maxSlots - $reservedCount }} créneau(x)</strong> (optionnel).
                                </p>
                            @endif

                            <div class="mt-3 flex items-start gap-2.5 rounded-xl bg-amber-50 p-3.5 text-sm text-amber-800">
                                <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                <p><strong>Attention :</strong> une fois validé, ton planning est verrouillé. Seul un administrateur pourra le modifier.</p>
                            </div>

                            <button type="button" data-open-dialog="finalize-dialog" @disabled($reservedCount < $minSlots || $isAwaitingMinorValidation)
                                class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-brand-500 font-semibold text-white shadow-md transition hover:bg-brand-600 hover:shadow-lg disabled:cursor-not-allowed disabled:bg-stone-300 disabled:shadow-none">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                Valider définitivement
                            </button>
                        @endif
                    </div>
                </aside>
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-sand-200 bg-white/95 px-4 py-3 shadow-[0_-4px_12px_rgba(92,25,17,0.06)] backdrop-blur lg:hidden">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                <div>
                    <p class="font-heading font-bold text-brand-500">{{ $reservedCount }} / {{ $maxSlots }} créneaux</p>
                    <p class="text-xs text-stone-500">{{ $statusTitle }}</p>
                </div>
                <a href="#recapitulatif" class="rounded-full bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                    Mon récapitulatif
                </a>
            </div>
        </div>

        @unless ($isReadOnly)
            <dialog id="finalize-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-sand-200 bg-white p-6 shadow-2xl backdrop:bg-[#1e1e24]/50 backdrop:backdrop-blur-sm sm:p-8">
                <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-500">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                </div>
                <h2 class="font-heading text-xl font-bold">Verrouiller définitivement ton planning ?</h2>
                <p class="mt-2 text-stone-600">
                    Tu valides tes créneaux pour <strong class="text-stone-900">{{ $edition->name }}</strong>. Il ne sera ensuite modifiable que par un administrateur.
                </p>

                <ul class="my-5 flex flex-col gap-2 rounded-xl bg-sand-50 p-4 text-sm">
                    @foreach ($assignments as $assignment)
                        <li class="flex flex-wrap justify-between gap-x-3">
                            <span class="text-stone-500">
                                {{ $assignment->missionSlot->timeSlot->eventDay->label }}
                                {{ substr($assignment->missionSlot->timeSlot->starts_at, 0, 5) }}–{{ substr($assignment->missionSlot->timeSlot->ends_at, 0, 5) }}
                            </span>
                            <span class="font-semibold">{{ $assignment->missionSlot->mission->name }}</span>
                        </li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('planning.finalize') }}" class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    @csrf
                    <button type="button" data-close-dialog class="rounded-full px-5 py-2.5 font-semibold text-stone-600 hover:bg-sand-100">
                        Revenir aux créneaux
                    </button>
                    <button type="submit" class="rounded-full bg-brand-500 px-6 py-2.5 font-semibold text-white shadow-sm hover:bg-brand-600">
                        Confirmer mon engagement
                    </button>
                </form>
            </dialog>

            <script>
                document.querySelectorAll('[data-open-dialog]').forEach((button) => {
                    button.addEventListener('click', () => document.getElementById(button.dataset.openDialog).showModal());
                });

                document.querySelectorAll('[data-close-dialog]').forEach((button) => {
                    button.addEventListener('click', () => button.closest('dialog').close());
                });
            </script>
        @endunless
    </body>
</html>
