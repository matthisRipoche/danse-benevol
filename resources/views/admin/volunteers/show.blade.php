<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $volunteer->first_name }} {{ $volunteer->last_name }} — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        @php
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;
            $statusLabels = ['draft' => 'Brouillon', 'validated' => 'Validé'];
        @endphp

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <a href="{{ url()->previous() !== url()->current() && str_contains(url()->previous(), '/admin/benevoles') ? url()->previous() : route('admin.volunteers.index') }}"
                class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-brand-500">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Retour à la liste
            </a>

            @include('admin.partials.flash')

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
                <section class="rounded-xl border border-sand-200 bg-white p-5 lg:sticky lg:top-24">
                    <div class="flex items-center gap-4">
                        <div class="size-16 shrink-0 overflow-hidden rounded-full bg-sand-100 ring-2 ring-sand-200">
                            @if ($volunteer->photo_path)
                                <img src="{{ route('admin.volunteers.photo', $volunteer) }}" alt="Photo de {{ $volunteer->first_name }}" class="size-full object-cover">
                            @else
                                <span class="flex size-full items-center justify-center font-bold text-brand-500">
                                    {{ mb_substr($volunteer->first_name, 0, 1) }}{{ mb_substr($volunteer->last_name, 0, 1) }}
                                </span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h1 class="font-heading text-xl font-bold break-words">{{ $volunteer->first_name }} {{ $volunteer->last_name }}</h1>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $isValidated,
                                    'bg-sand-100 text-stone-600' => ! $isValidated,
                                ])>{{ $isValidated ? 'Planning validé' : 'Planning en attente' }}</span>
                                @if ($volunteer->is_minor)
                                    @include('admin.volunteers.partials.minor-status')
                                @endif
                            </div>
                        </div>
                    </div>

                    <dl class="mt-5 flex flex-col gap-3 border-t border-sand-100 pt-4 text-sm">
                        <div>
                            <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">E-mail</dt>
                            <dd><a href="mailto:{{ $volunteer->email }}" class="font-medium break-all text-brand-500 hover:underline">{{ $volunteer->email }}</a></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">Téléphone</dt>
                            <dd><a href="tel:{{ $volunteer->phone }}" class="font-medium text-brand-500 hover:underline">{{ $volunteer->phone }}</a></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">Inscrit(e) le</dt>
                            <dd class="font-medium">
                                {{ $volunteer->created_at->format('d/m/Y') }}
                                @if ($volunteer->usedInvitationCode)
                                    <span class="font-mono text-stone-500">· {{ $volunteer->usedInvitationCode->code }}</span>
                                @endif
                            </dd>
                        </div>
                        @if ($isValidated)
                            <div>
                                <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">Validé le</dt>
                                <dd class="font-medium">{{ $validatedAt?->format('d/m/Y à H:i') ?? '—' }}</dd>
                            </div>
                        @endif
                        @if ($badgeUid)
                            <div>
                                <dt class="text-xs font-semibold tracking-wider text-stone-500 uppercase">Badge</dt>
                                <dd class="font-mono font-medium">SDLD-{{ strtoupper(substr($badgeUid, 0, 8)) }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="rounded-xl border border-sand-200 bg-white p-5 lg:col-span-2">
                    <div class="mb-5 flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="font-heading text-lg font-bold">Planning · {{ $edition->name }}</h2>
                        <p class="text-sm text-stone-500">
                            {{ $assignmentCount }} créneau(x)
                            @if ($totalMinutes > 0)
                                · {{ $hours }} h{{ $minutes > 0 ? sprintf('%02d', $minutes) : '' }}
                            @endif
                        </p>
                    </div>

                    @forelse ($assignmentsByDay as $dayAssignments)
                        @php
                            $day = $dayAssignments->first()->missionSlot->timeSlot->eventDay;
                        @endphp
                        <div class="mb-5 last:mb-0">
                            <h3 class="mb-2 text-sm font-bold tracking-wider text-brand-500 uppercase">
                                {{ $day->label }} <span class="font-semibold text-stone-500">{{ $day->date->format('d/m/Y') }}</span>
                            </h3>
                            <ul class="divide-y divide-sand-100 rounded-lg border border-sand-100">
                                @foreach ($dayAssignments as $assignment)
                                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-3 py-2.5">
                                        <span class="w-28 shrink-0 font-mono text-sm font-semibold">
                                            {{ substr($assignment->missionSlot->timeSlot->starts_at, 0, 5) }}–{{ substr($assignment->missionSlot->timeSlot->ends_at, 0, 5) }}
                                        </span>
                                        <span class="min-w-0 flex-1 font-medium">{{ $assignment->missionSlot->mission->name }}</span>
                                        <span class="flex gap-1.5">
                                            @unless ($assignment->missionSlot->mission->is_public)
                                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-500">Restreint</span>
                                            @endunless
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-xs font-semibold',
                                                'bg-emerald-100 text-emerald-800' => $assignment->status === 'validated',
                                                'bg-sand-100 text-stone-600' => $assignment->status !== 'validated',
                                            ])>{{ $statusLabels[$assignment->status] ?? $assignment->status }}</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="rounded-lg bg-sand-50 p-4 text-sm text-stone-500">Aucun créneau réservé pour l'instant.</p>
                    @endforelse
                </section>
            </div>
        </main>
    </body>
</html>
