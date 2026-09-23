@if ($assignments->isEmpty())
    <span class="text-stone-400">Aucun créneau</span>
@else
    <ul class="flex flex-col gap-1">
        @foreach ($assignments as $assignment)
            <li class="flex items-baseline gap-2">
                <span class="w-9 shrink-0 text-xs font-bold text-brand-500 uppercase">{{ mb_substr($assignment->missionSlot->timeSlot->eventDay->label, 0, 3) }}.</span>
                <span class="shrink-0 font-mono text-xs text-stone-600">{{ substr($assignment->missionSlot->timeSlot->starts_at, 0, 5) }}–{{ substr($assignment->missionSlot->timeSlot->ends_at, 0, 5) }}</span>
                <span class="min-w-0 truncate">{{ $assignment->missionSlot->mission->name }}</span>
            </li>
        @endforeach
    </ul>
@endif
