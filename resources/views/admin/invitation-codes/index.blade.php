<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Codes d'invitation — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        @php
            $statusLabels = ['pending' => 'En attente', 'used' => 'Utilisé', 'revoked' => 'Révoqué'];
        @endphp

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-heading text-2xl font-bold">Codes d'invitation</h1>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.invitation-codes.import') }}"
                        class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm ring-1 ring-sand-200 hover:bg-sand-100">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                        Importer des candidats
                    </a>
                    <a href="{{ route('admin.invitation-codes.create') }}"
                        class="inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Nouveau code
                    </a>
                </div>
            </div>

            @include('admin.partials.flash')

            @include('admin.partials.import-report')

            <div class="overflow-hidden rounded-xl border border-sand-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-sand-100/60 text-xs tracking-wider text-stone-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Code</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                            <th class="hidden px-4 py-3 font-semibold sm:table-cell">Expire le</th>
                            <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-100">
                        @forelse ($codes as $code)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-mono font-semibold tracking-wider">{{ $code->code }}</p>
                                    <p class="break-all text-stone-500">{{ $code->email }}</p>
                                    <p class="text-xs text-stone-500 sm:hidden">Expire le {{ $code->expires_at?->format('d/m/Y') ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold whitespace-nowrap',
                                        'bg-amber-100 text-amber-800' => $code->status === 'pending',
                                        'bg-emerald-100 text-emerald-800' => $code->status === 'used',
                                        'bg-sand-100 text-stone-500' => $code->status === 'revoked',
                                    ])>
                                        {{ $statusLabels[$code->status] ?? $code->status }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 text-stone-600 sm:table-cell">{{ $code->expires_at?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($code->status === 'pending')
                                        <form method="POST" action="{{ route('admin.invitation-codes.revoke', $code) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200 hover:bg-red-50">
                                                Révoquer
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-stone-500">
                                    Aucun code d'invitation pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </body>
</html>
