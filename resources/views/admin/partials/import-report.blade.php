@if (session('importSkipped'))
    <details class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" open>
        <summary class="cursor-pointer font-semibold">{{ count(session('importSkipped')) }} ligne(s) ignorée(s) lors de l'import</summary>
        <ul class="mt-2 flex flex-col gap-1">
            @foreach (session('importSkipped') as $skippedLine)
                <li>
                    <span class="font-mono text-xs">Ligne {{ $skippedLine['line'] }}</span>
                    · <span class="break-all">{{ $skippedLine['value'] }}</span>
                    · {{ $skippedLine['reason'] }}
                </li>
            @endforeach
        </ul>
    </details>
@endif
