@php
    $field = 'h-11 w-full rounded-lg border border-sand-200 bg-white px-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none';
    $isPublic = (bool) old('is_public', $mission?->is_public ?? true);
@endphp

<div class="flex flex-col gap-1">
    <label for="name" class="text-sm font-medium text-stone-600">Nom de la mission</label>
    <input type="text" name="name" id="name" value="{{ old('name', $mission?->name) }}" required maxlength="255"
        class="{{ $field }} @error('name') border-red-400 @enderror">
</div>

<div class="flex flex-col gap-1">
    <label for="description" class="text-sm font-medium text-stone-600">Description <span class="font-normal text-stone-400">(facultatif, visible des bénévoles)</span></label>
    <textarea name="description" id="description" rows="3" maxlength="2000"
        class="w-full rounded-lg border border-sand-200 bg-white px-3 py-2 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none">{{ old('description', $mission?->description) }}</textarea>
</div>

<fieldset class="flex flex-col gap-2">
    <legend class="mb-1 text-sm font-medium text-stone-600">Visibilité</legend>
    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-sand-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
        <input type="radio" name="is_public" value="1" @checked($isPublic) class="mt-1 accent-brand-500">
        <span><span class="font-semibold">Publique</span><span class="block text-sm text-stone-500">Les bénévoles la réservent eux-mêmes dans leur planning.</span></span>
    </label>
    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-sand-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
        <input type="radio" name="is_public" value="0" @checked(! $isPublic) class="mt-1 accent-brand-500">
        <span><span class="font-semibold">Restreinte</span><span class="block text-sm text-stone-500">Invisible des bénévoles : un admin l'attribue depuis « Postes restreints ».</span></span>
    </label>
</fieldset>

<div class="flex flex-col gap-1">
    <label for="default_capacity" class="text-sm font-medium text-stone-600">Places par créneau (par défaut)</label>
    <input type="number" name="default_capacity" id="default_capacity" value="{{ old('default_capacity', $mission?->default_capacity ?? 5) }}" required min="0" max="500"
        class="{{ $field }} max-w-40 @error('default_capacity') border-red-400 @enderror">
    <p class="text-xs text-stone-500">{{ $defaultCapacityHint }}</p>
</div>
