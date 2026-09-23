<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Mon profil & badge — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased print:bg-white">
        @include('partials.volunteer-header')

        @php
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;
            $badgeId = $badgeUid ? 'SDLD-'.strtoupper(substr($badgeUid, 0, 8)) : null;
        @endphp

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <section class="mb-8 flex flex-col gap-4 rounded-2xl border border-sand-200 bg-white p-5 shadow-sm sm:p-6 lg:flex-row lg:items-center lg:justify-between print:hidden">
                <div class="flex items-start gap-4">
                    <div @class([
                        'flex size-12 shrink-0 items-center justify-center rounded-full',
                        'bg-emerald-100 text-emerald-700' => $isValidated,
                        'bg-amber-100 text-amber-700' => ! $isValidated,
                    ])>
                        @if ($isValidated)
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                        @else
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="font-heading text-xl font-bold">
                                {{ $isValidated ? 'Planning validé & badge actif' : 'Badge en attente' }}
                            </h1>
                            @if ($isValidated)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold tracking-wider text-emerald-800 uppercase">
                                    <span class="size-1.5 rounded-full bg-emerald-600"></span>
                                    Verrouillé
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-stone-600">
                            @if ($isValidated)
                                Ton planning pour <strong class="text-stone-900">{{ $edition->name }}</strong> est validé. Seul un administrateur peut le modifier désormais.
                            @else
                                Ton badge sera généré dès que tu auras validé définitivement ton planning.
                            @endif
                        </p>
                    </div>
                </div>

                @if ($isValidated)
                    <button type="button" onclick="window.print()"
                        class="flex h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-brand-500 px-5 font-semibold text-white shadow-sm hover:bg-brand-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Imprimer mon badge
                    </button>
                @else
                    <a href="{{ route('planning.index') }}"
                        class="flex h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-brand-500 px-5 font-semibold text-white shadow-sm hover:bg-brand-600">
                        Compléter mon planning
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                @endif
            </section>

            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                <div class="flex flex-col items-center lg:col-span-5">
                    <div class="mb-3 flex w-full max-w-sm items-center gap-2 font-heading font-semibold print:hidden">
                        <svg class="size-5 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M15 10h3M15 14h3M6 16c.5-1.5 1.7-2 3-2s2.5.5 3 2"/></svg>
                        Badge d'accréditation
                    </div>

                    <div class="z-10 mx-auto -mb-1 h-4 w-8 rounded-t-md bg-sand-200 shadow-inner print:hidden"></div>
                    <article @class([
                        'relative w-full max-w-sm overflow-hidden rounded-2xl border border-sand-200 bg-white shadow-xl [print-color-adjust:exact] print:shadow-none',
                        'opacity-60 grayscale' => ! $isValidated,
                    ])>
                        <div class="h-2 bg-brand-500"></div>
                        <div class="px-6 pt-5 pb-2">
                            <div class="flex items-center justify-between gap-4">
                                <span class="font-heading text-lg leading-tight font-bold text-brand-500">Salon de la Danse</span>
                                <span class="text-right text-xs font-semibold tracking-widest text-stone-500 uppercase">{{ $edition->name }}</span>
                            </div>
                            <div class="mx-auto mt-4 h-2 w-14 rounded-full bg-sand-200"></div>
                        </div>

                        <div class="flex flex-col items-center px-6 pt-3 pb-6 text-center">
                            <div class="relative mb-4">
                                <div class="size-28 overflow-hidden rounded-full bg-sand-100 p-1 ring-4 ring-sand-100">
                                    @if ($user->photo_path)
                                        <img src="{{ route('profile.photo') }}" alt="Photo de {{ $user->first_name }}" class="size-full rounded-full object-cover">
                                    @else
                                        <div class="flex size-full items-center justify-center rounded-full text-sand-400">
                                            <svg class="size-12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0Z"/></svg>
                                        </div>
                                    @endif
                                </div>
                                @if ($isValidated)
                                    <div class="absolute right-1 bottom-0 flex size-7 items-center justify-center rounded-full bg-emerald-600 text-white shadow ring-2 ring-white">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    </div>
                                @endif
                            </div>

                            <h2 class="font-heading text-2xl font-bold break-words">{{ $user->first_name }} {{ $user->last_name }}</h2>
                            <span class="mt-2 rounded-full bg-brand-500 px-4 py-1 text-sm font-bold tracking-wider text-white uppercase">Bénévole</span>

                            <div class="mt-5 flex flex-col items-center gap-2">
                                @if ($badgeQrCode)
                                    <div class="size-36 rounded-xl border border-sand-200 bg-white p-2 [&>svg]:size-full" role="img" aria-label="QR code du badge {{ $badgeId }}">
                                        {!! $badgeQrCode !!}
                                    </div>
                                @else
                                    <div class="flex size-36 items-center justify-center rounded-xl border-2 border-dashed border-sand-200 p-3 text-xs text-stone-500">
                                        QR code généré à la validation
                                    </div>
                                @endif
                                <span class="font-mono text-xs tracking-widest text-stone-500">
                                    {{ $badgeId ? 'ID : '.$badgeId : 'ID attribué à la validation' }}
                                </span>
                            </div>

                            @if ($assignments->isNotEmpty())
                                <div class="mt-5 w-full">
                                    <span class="mb-2 block text-xs font-semibold tracking-wider text-stone-500 uppercase">Postes</span>
                                    <div class="flex flex-wrap justify-center gap-1.5">
                                        @foreach ($assignments->pluck('missionSlot.mission.name')->unique() as $missionName)
                                            <span class="rounded-full bg-sand-100 px-2.5 py-1 text-xs font-semibold text-stone-700">{{ $missionName }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($edition->start_date && $edition->end_date)
                            <div class="border-t border-sand-200 bg-sand-50 px-4 py-2 text-center text-sm text-stone-600">
                                Angers • du {{ $edition->start_date->format('d/m') }} au {{ $edition->end_date->format('d/m/Y') }}
                            </div>
                        @endif
                    </article>

                    <p class="mt-4 w-full max-w-sm rounded-xl bg-sand-100 p-4 text-center text-sm text-stone-600 print:hidden">
                        <strong class="text-stone-900">Conseil :</strong> garde ce badge sur ton téléphone ou imprime-le. Le QR code permet à l'équipe d'organisation de vérifier ton identité à ton arrivée.
                    </p>
                </div>

                <div class="flex min-w-0 flex-col gap-6 lg:col-span-7 print:hidden">
                    <section class="rounded-2xl border border-sand-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <span class="block text-xs font-bold tracking-wider text-sand-400 uppercase">Feuille de route</span>
                                <h2 class="font-heading text-xl font-bold">
                                    {{ $assignments->count() }} {{ $assignments->count() > 1 ? 'créneaux' : 'créneau' }} {{ $isValidated ? 'validé'.($assignments->count() > 1 ? 's' : '') : 'en brouillon' }}
                                </h2>
                            </div>
                            @if ($totalMinutes > 0)
                                <span class="rounded-full bg-sand-100 px-3 py-1 text-sm font-semibold text-brand-500">
                                    Total : {{ $hours }} h{{ $minutes > 0 ? sprintf('%02d', $minutes) : '' }} d'engagement
                                </span>
                            @endif
                        </div>

                        @if ($assignments->isEmpty())
                            <p class="rounded-xl bg-sand-50 p-4 text-sm text-stone-500">
                                Aucun créneau pour l'instant.
                                <a href="{{ route('planning.index') }}" class="font-semibold text-brand-500 hover:underline">Choisir mes créneaux</a>
                            </p>
                        @else
                            <ol class="relative flex flex-col gap-4 pl-7 before:absolute before:top-3 before:bottom-3 before:left-2.5 before:w-0.5 before:bg-sand-200">
                                @foreach ($assignments as $assignment)
                                    <li class="relative">
                                        <span class="absolute top-4 -left-7 flex size-5 items-center justify-center rounded-full bg-brand-500 ring-4 ring-white">
                                            <span class="size-1.5 rounded-full bg-white"></span>
                                        </span>
                                        <div class="rounded-xl bg-sand-50 p-4">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="font-semibold text-brand-500">
                                                    {{ $assignment->missionSlot->timeSlot->eventDay->label }}
                                                    {{ $assignment->missionSlot->timeSlot->eventDay->date->format('d/m/Y') }}
                                                </span>
                                                <span class="rounded-full bg-sand-200 px-2.5 py-0.5 text-sm font-semibold">
                                                    {{ substr($assignment->missionSlot->timeSlot->starts_at, 0, 5) }} – {{ substr($assignment->missionSlot->timeSlot->ends_at, 0, 5) }}
                                                </span>
                                            </div>
                                            <h3 class="mt-1 font-heading text-lg font-semibold">{{ $assignment->missionSlot->mission->name }}</h3>
                                            @if ($assignment->missionSlot->mission->description)
                                                <p class="mt-1 text-sm text-stone-600">{{ $assignment->missionSlot->mission->description }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-sand-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <h2 class="font-heading text-xl font-bold">Mes informations</h2>
                            @if ($user->profile_locked_at)
                                <span class="inline-flex items-center gap-1 rounded-full bg-sand-100 px-2.5 py-1 text-xs font-semibold text-stone-600">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                    Profil verrouillé
                                </span>
                            @endif
                        </div>

                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">Nom complet</dt>
                                <dd class="mt-0.5 font-semibold break-words">{{ $user->first_name }} {{ $user->last_name }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">E-mail</dt>
                                <dd class="mt-0.5 font-semibold break-all">{{ $user->email }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">Téléphone</dt>
                                <dd class="mt-0.5 font-semibold">{{ $user->phone }}</dd>
                            </div>
                            @if ($user->is_minor)
                                <div class="min-w-0">
                                    <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">Profil mineur</dt>
                                    <dd class="mt-0.5 font-semibold">
                                        @if ($user->minor_validated_at)
                                            <span class="text-emerald-700">Validé par l'organisation</span>
                                        @else
                                            <span class="text-amber-700">En attente de validation</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        @if ($user->profile_locked_at)
                            <p class="mt-4 rounded-xl bg-sand-50 p-3 text-sm text-stone-600">
                                Ton profil est verrouillé depuis la validation de ton planning. Pour toute correction, contacte un administrateur.
                            </p>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </body>
</html>
