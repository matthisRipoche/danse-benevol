{{--
    Personal information fields shared by the volunteer and admin edit forms.
    Expects: $user, $photoUrl (current photo or null), $showMinorField (bool).
--}}
@php
    $field = 'h-11 w-full rounded-lg border border-sand-200 bg-white px-3 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/15 focus:outline-none';
@endphp

<div class="grid grid-cols-2 gap-3">
    <div class="flex flex-col gap-1">
        <label for="first_name" class="text-sm font-medium text-stone-600">Prénom</label>
        <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required maxlength="255" autocomplete="given-name"
            class="{{ $field }} @error('first_name') border-red-400 @enderror">
    </div>
    <div class="flex flex-col gap-1">
        <label for="last_name" class="text-sm font-medium text-stone-600">Nom</label>
        <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required maxlength="255" autocomplete="family-name"
            class="{{ $field }} @error('last_name') border-red-400 @enderror">
    </div>
</div>

<div class="flex flex-col gap-1">
    <label for="email" class="text-sm font-medium text-stone-600">Adresse e-mail</label>
    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email"
        class="{{ $field }} @error('email') border-red-400 @enderror">
</div>

<div class="flex flex-col gap-1">
    <label for="phone" class="text-sm font-medium text-stone-600">Numéro de téléphone</label>
    <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" required maxlength="20" autocomplete="tel"
        class="{{ $field }} @error('phone') border-red-400 @enderror">
</div>

@if ($showMinorField)
    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg bg-sand-50 p-3 text-sm">
        <input type="checkbox" name="is_minor" value="1" @checked(old('is_minor', $user->is_minor))
            class="mt-0.5 size-4 shrink-0 accent-brand-500">
        <span>
            <span class="font-semibold">Bénévole mineur(e)</span>
            <span class="block text-stone-500">Décocher annule la validation du profil mineur, s'il y en avait une.</span>
        </span>
    </label>
@endif

<div class="flex items-center gap-4 rounded-xl bg-sand-100 p-4">
    <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-sand-200 text-sand-400">
        <img id="photo-preview" src="{{ $photoUrl }}" alt="Photo de {{ $user->first_name }}" @class(['size-full object-cover', 'hidden' => ! $photoUrl])>
        <svg id="photo-placeholder" @class(['size-10', 'hidden' => $photoUrl]) viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0Z"/></svg>
    </div>
    <div class="flex min-w-0 flex-col gap-1">
        <p class="font-semibold">Photo du badge</p>
        <p class="text-sm text-stone-600">De face, tête nue, nette. Laisse vide pour garder la photo actuelle.</p>
        <label for="photo" class="mt-1 inline-flex w-fit cursor-pointer items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-semibold shadow-sm hover:bg-sand-50 has-[:focus-visible]:ring-3 has-[:focus-visible]:ring-brand-500/30 @error('photo') ring-2 ring-red-400 @enderror">
            <svg class="size-4 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z"/><circle cx="12" cy="13" r="3"/></svg>
            <span id="photo-label">Changer la photo</span>
            <input type="file" name="photo" id="photo" accept="image/*" class="sr-only">
        </label>
    </div>
</div>

<script>
    document.getElementById('photo').addEventListener('change', (event) => {
        const file = event.target.files[0];

        if (! file) {
            return;
        }

        const preview = document.getElementById('photo-preview');
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        document.getElementById('photo-placeholder').classList.add('hidden');
        document.getElementById('photo-label').textContent = file.name;
    });
</script>
