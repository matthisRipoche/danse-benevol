@php
    $tabLink = fn (bool $active) => $active
        ? 'border-brand-500 text-brand-500'
        : 'border-transparent text-stone-500 hover:text-brand-500';
@endphp
<nav class="mb-6 flex gap-6 border-b border-sand-200 text-sm font-semibold" aria-label="Missions">
    <a href="{{ route('admin.missions.index') }}" @class(['-mb-px border-b-2 pb-2', $tabLink(request()->routeIs('admin.missions.*'))])>Missions</a>
    <a href="{{ route('admin.schedule.index') }}" @class(['-mb-px border-b-2 pb-2', $tabLink(request()->routeIs('admin.schedule.*'))])>Jours et créneaux</a>
</nav>
