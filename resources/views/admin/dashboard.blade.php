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
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-heading text-2xl font-bold">
                    Tableau de bord <span class="text-stone-400">·</span> <span class="text-brand-500">{{ $edition->name }}</span>
                </h1>
                <a href="{{ route('admin.editions.index') }}"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm ring-1 ring-sand-200 hover:bg-sand-100">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Gérer les éditions
                </a>
            </div>

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
