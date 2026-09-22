<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Postes restreints — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="mx-auto max-w-6xl p-4 sm:p-6 lg:p-8">
            @include('admin.partials.nav')

            <h1 class="mb-6 text-lg font-semibold">Postes restreints — {{ $edition->name }}</h1>

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

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @foreach ($days as $day)
                <section class="mb-8">
                    <h2 class="mb-2 font-semibold">{{ $day->label }} — {{ $day->date->format('d/m/Y') }}</h2>

                    @foreach ($day->timeSlots as $timeSlot)
                        @foreach ($timeSlot->missionSlots as $missionSlot)
                            <div class="mb-3 rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <p class="font-medium">
                                        {{ $missionSlot->mission->name }}
                                        — {{ substr($timeSlot->starts_at, 0, 5) }}–{{ substr($timeSlot->ends_at, 0, 5) }}
                                    </p>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $missionSlot->volunteerAssignments->count() }}/{{ $missionSlot->capacity }}
                                    </span>
                                </div>

                                <ul class="mb-2 text-sm">
                                    @forelse ($missionSlot->volunteerAssignments as $assignment)
                                        <li class="flex items-center justify-between py-1">
                                            <span>{{ $assignment->user->first_name }} {{ $assignment->user->last_name }}</span>
                                            <form method="POST" action="{{ route('admin.restricted-missions.unassign', $assignment) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">
                                                    Retirer
                                                </button>
                                            </form>
                                        </li>
                                    @empty
                                        <li class="text-gray-500 dark:text-gray-400">Personne assigné(e) pour l'instant.</li>
                                    @endforelse
                                </ul>

                                @if ($missionSlot->remainingCapacity() > 0)
                                    <form method="POST" action="{{ route('admin.restricted-missions.assign', $missionSlot) }}" class="flex gap-2">
                                        @csrf
                                        <input type="email" name="email" placeholder="E-mail du bénévole" required
                                            class="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900">
                                        <button type="submit" class="rounded-md bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">
                                            Assigner
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </section>
            @endforeach
        </div>
    </body>
</html>
