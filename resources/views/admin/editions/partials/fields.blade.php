@php
    $field = 'h-11 w-full rounded-lg border border-sand-200 bg-white px-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none';
@endphp

<div class="flex flex-col gap-1">
    <label for="name" class="text-sm font-medium text-stone-600">Nom de l'édition</label>
    <input type="text" name="name" id="name" value="{{ old('name', $edition?->name) }}" required maxlength="255" placeholder="Salon de la Danse 2028"
        class="{{ $field }} @error('name') border-red-400 @enderror">
</div>

<div class="grid grid-cols-2 gap-3">
    <div class="flex flex-col gap-1">
        <label for="start_date" class="text-sm font-medium text-stone-600">Premier jour</label>
        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $edition?->start_date?->format('Y-m-d')) }}" required
            class="{{ $field }} @error('start_date') border-red-400 @enderror">
    </div>
    <div class="flex flex-col gap-1">
        <label for="end_date" class="text-sm font-medium text-stone-600">Dernier jour</label>
        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $edition?->end_date?->format('Y-m-d')) }}" required
            class="{{ $field }} @error('end_date') border-red-400 @enderror">
    </div>
</div>

<fieldset class="flex flex-col gap-2">
    <legend class="mb-1 text-sm font-medium text-stone-600">Règles du planning bénévole</legend>
    <div class="grid grid-cols-3 gap-3">
        @foreach ([
            'min_slots_per_volunteer' => ['Créneaux min.', 1],
            'max_slots_per_volunteer' => ['Créneaux max.', 3],
            'max_consecutive_slots' => ['Consécutifs max.', 2],
        ] as $quotaField => [$quotaLabel, $quotaDefault])
            <div class="flex flex-col gap-1">
                <label for="{{ $quotaField }}" class="text-xs font-medium text-stone-600">{{ $quotaLabel }}</label>
                <input type="number" name="{{ $quotaField }}" id="{{ $quotaField }}" min="1" max="20" required
                    value="{{ old($quotaField, $edition?->{$quotaField} ?? $quotaDefault) }}"
                    class="{{ $field }} @error($quotaField) border-red-400 @enderror">
            </div>
        @endforeach
    </div>
    <p class="text-xs text-stone-500">Nombre de créneaux qu'un bénévole peut réserver sur l'édition, et nombre de créneaux qu'il peut enchaîner le même jour.</p>
</fieldset>
