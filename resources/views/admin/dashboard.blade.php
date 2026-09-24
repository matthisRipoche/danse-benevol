<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Tableau de bord — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <h1 class="mb-6 font-heading text-2xl font-bold">
                Tableau de bord <span class="text-stone-400">·</span> <span class="text-brand-500">{{ $edition->name }}</span>
            </h1>

            @include('admin.partials.flash')

            @php
                $registrationStatus = $edition->registrationStatus();
                $toLocalInput = fn ($date) => $date?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\\TH:i');
                $registrationBadges = [
                    'open' => ['Ouvertes', 'bg-emerald-100 text-emerald-800'],
                    'not_open' => ['Pas encore ouvertes', 'bg-amber-100 text-amber-800'],
                    'closed' => ['Closes', 'bg-sand-100 text-stone-600'],
                    'locked' => ['Suspendues', 'bg-red-100 text-red-800'],
                ];
                [$registrationLabel, $registrationBadgeClass] = $registrationBadges[$registrationStatus];
            @endphp

            <section class="mb-8 rounded-xl border border-sand-200 bg-white p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-heading text-lg font-semibold">Inscriptions des bénévoles</h2>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $registrationBadgeClass }}">{{ $registrationLabel }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.registration-window.toggle-lock') }}"
                        onsubmit="return confirm('{{ $edition->is_registration_locked ? 'Rouvrir les inscriptions selon les dates prévues ?' : 'Suspendre les inscriptions ? Les plannings des bénévoles passeront en lecture seule.' }}');">
                        @csrf
                        <button type="submit" @class([
                            'rounded-full px-4 py-2 text-sm font-semibold shadow-sm',
                            'bg-brand-500 text-white hover:bg-brand-600' => $edition->is_registration_locked,
                            'bg-white text-red-700 ring-1 ring-red-200 hover:bg-red-50' => ! $edition->is_registration_locked,
                        ])>
                            {{ $edition->is_registration_locked ? 'Rouvrir les inscriptions' : 'Suspendre les inscriptions' }}
                        </button>
                    </form>
                </div>
                <p class="mb-4 text-sm text-stone-500">
                    En dehors de ces dates, ou pendant une suspension, les bénévoles voient leur planning sans pouvoir réserver, annuler ni valider. Heure de Paris ; une date vide laisse ce côté ouvert.
                </p>
                <form method="POST" action="{{ route('admin.registration-window.update') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PUT')
                    @foreach (['registration_opens_at' => 'Ouverture', 'registration_closes_at' => 'Fermeture'] as $windowField => $windowLabel)
                        <div class="flex flex-col gap-1">
                            <label for="{{ $windowField }}" class="text-sm font-medium text-stone-600">{{ $windowLabel }}</label>
                            <input type="datetime-local" name="{{ $windowField }}" id="{{ $windowField }}"
                                value="{{ old($windowField, $toLocalInput($edition->{$windowField})) }}"
                                class="h-10 rounded-lg border border-sand-200 bg-white px-3 text-sm focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none @error($windowField) border-red-400 @enderror">
                        </div>
                    @endforeach
                    <button type="submit" class="h-10 rounded-full bg-white px-4 text-sm font-semibold text-stone-700 shadow-sm ring-1 ring-sand-200 hover:bg-sand-100">
                        Enregistrer les dates
                    </button>
                </form>
            </section>

            <div class="mb-8 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                <div class="rounded-xl border border-sand-200 bg-white p-4">
                    <p class="text-sm text-stone-500">Codes émis</p>
                    <p class="mt-1 font-heading text-3xl font-bold">{{ $totalInvitationCodes }}</p>
                    <p class="mt-1 text-xs text-stone-500">
                        {{ $invitationCodeCounts->get('pending', 0) }} en attente
                        · {{ $invitationCodeCounts->get('used', 0) }} utilisés
                        · {{ $invitationCodeCounts->get('revoked', 0) }} révoqués
                    </p>
                </div>

                <div class="rounded-xl border border-sand-200 bg-white p-4">
                    <p class="text-sm text-stone-500">Comptes créés</p>
                    <p class="mt-1 font-heading text-3xl font-bold">{{ $totalVolunteers }}</p>
                </div>

                <div class="rounded-xl border border-sand-200 bg-white p-4">
                    <p class="text-sm text-stone-500">Plannings validés</p>
                    <p class="mt-1 font-heading text-3xl font-bold">{{ $validatedVolunteers }}/{{ $totalVolunteers }}</p>
                    <p class="mt-1 text-xs text-stone-500">{{ $pendingVolunteers }} en attente</p>
                </div>

                <div class="rounded-xl border border-sand-200 bg-white p-4">
                    <p class="text-sm text-stone-500">Taux de remplissage</p>
                    <p class="mt-1 font-heading text-3xl font-bold text-brand-500">{{ $globalFillRate }}%</p>
                    <p class="mt-1 text-xs text-stone-500">{{ $totalFilled }}/{{ $totalCapacity }} places</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @foreach (['Remplissage par jour' => $fillRateByDay, 'Remplissage par mission' => $fillRateByMission] as $title => $rows)
                    <section class="rounded-xl border border-sand-200 bg-white p-5">
                        <h2 class="mb-4 font-heading font-semibold">{{ $title }}</h2>
                        <ul class="flex flex-col gap-4">
                            @forelse ($rows as $row)
                                <li>
                                    <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                                        <span class="min-w-0 truncate font-medium">{{ $row->label }}</span>
                                        <span class="shrink-0 text-stone-500">
                                            {{ $row->filled }}/{{ $row->capacity }}
                                            <span class="ml-2 inline-block w-10 text-right font-semibold text-stone-900">{{ $row->rate }}%</span>
                                        </span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-sand-100">
                                        <div @class([
                                            'h-full rounded-full',
                                            'bg-emerald-600' => $row->rate >= 80,
                                            'bg-brand-500' => $row->rate >= 40 && $row->rate < 80,
                                            'bg-amber-500' => $row->rate < 40,
                                        ]) style="width: {{ min(100, $row->rate) }}%"></div>
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-stone-500">Aucune donnée pour l'instant.</li>
                            @endforelse
                        </ul>
                    </section>
                @endforeach
            </div>
        </main>
    </body>
</html>
