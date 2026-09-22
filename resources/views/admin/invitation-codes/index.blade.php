<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Codes d'invitation — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="mx-auto max-w-6xl p-4 sm:p-6 lg:p-8">
            @include('admin.partials.nav')

            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-lg font-semibold">Codes d'invitation</h1>
                <a href="{{ route('admin.invitation-codes.create') }}"
                    class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    Nouveau code
                </a>
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

            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-300 dark:border-gray-600">
                        <th class="py-2 pr-4">E-mail</th>
                        <th class="py-2 pr-4">Code</th>
                        <th class="py-2 pr-4">Statut</th>
                        <th class="py-2 pr-4">Expire le</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($codes as $code)
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <td class="py-2 pr-4">{{ $code->email }}</td>
                            <td class="py-2 pr-4 font-mono">{{ $code->code }}</td>
                            <td class="py-2 pr-4">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' => $code->status === 'pending',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $code->status === 'used',
                                    'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => $code->status === 'revoked',
                                ])>
                                    {{ $code->status }}
                                </span>
                            </td>
                            <td class="py-2 pr-4">{{ $code->expires_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="py-2 text-right">
                                @if ($code->status === 'pending')
                                    <form method="POST" action="{{ route('admin.invitation-codes.revoke', $code) }}">
                                        @csrf
                                        <button type="submit" class="text-sm text-red-600 hover:underline dark:text-red-400">
                                            Révoquer
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500 dark:text-gray-400">
                                Aucun code d'invitation pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </body>
</html>
