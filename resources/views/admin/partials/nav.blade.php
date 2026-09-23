@php
    $navLink = fn (bool $active) => $active
        ? 'bg-brand-500 text-white shadow-sm'
        : 'text-stone-600 hover:bg-sand-100 hover:text-brand-500';
@endphp
<header class="sticky top-0 z-30 border-b border-sand-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2">
            <span class="font-heading font-bold text-brand-500">Salon de la Danse</span>
            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-bold tracking-wider text-brand-500 uppercase">Admin</span>
        </div>

        <div class="flex items-center gap-3 lg:order-last">
            <span class="hidden text-sm text-stone-600 md:inline">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            @include('partials.logout-button')
        </div>

        <nav class="-mx-4 flex w-[calc(100%+2rem)] gap-1 overflow-x-auto px-4 text-sm font-semibold sm:mx-0 sm:w-auto sm:px-0" aria-label="Administration">
            <a href="{{ route('admin.dashboard') }}" @class(['shrink-0 rounded-full px-3.5 py-1.5', $navLink(request()->routeIs('admin.dashboard'))])>Tableau de bord</a>
            <a href="{{ route('admin.volunteers.index') }}" @class(['shrink-0 rounded-full px-3.5 py-1.5', $navLink(request()->routeIs('admin.volunteers.*'))])>Bénévoles</a>
            <a href="{{ route('admin.invitation-codes.index') }}" @class(['shrink-0 rounded-full px-3.5 py-1.5', $navLink(request()->routeIs('admin.invitation-codes.*'))])>Codes d'invitation</a>
            <a href="{{ route('admin.missions.index') }}" @class(['shrink-0 rounded-full px-3.5 py-1.5', $navLink(request()->routeIs('admin.missions.*', 'admin.schedule.*'))])>Missions</a>
            <a href="{{ route('admin.restricted-missions.index') }}" @class(['shrink-0 rounded-full px-3.5 py-1.5', $navLink(request()->routeIs('admin.restricted-missions.*'))])>Postes restreints</a>
            <a href="{{ route('admin.exports.index') }}" @class(['shrink-0 rounded-full px-3.5 py-1.5', $navLink(request()->routeIs('admin.exports.*'))])>Exports</a>
        </nav>
    </div>
</header>
