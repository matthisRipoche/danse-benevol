<div class="mb-6 flex flex-col gap-3 border-b border-gray-200 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
    <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
        @php
            $link = fn (bool $active) => $active
                ? 'border-b-2 border-brand-500 font-semibold text-brand-500 dark:text-brand-400'
                : 'border-b-2 border-transparent text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400';
        @endphp
        <a href="{{ route('admin.dashboard') }}" class="pb-1 {{ $link(request()->routeIs('admin.dashboard')) }}">
            Tableau de bord
        </a>
        <a href="{{ route('admin.volunteers.index') }}" class="pb-1 {{ $link(request()->routeIs('admin.volunteers.*')) }}">
            Bénévoles
        </a>
        <a href="{{ route('admin.invitation-codes.index') }}" class="pb-1 {{ $link(request()->routeIs('admin.invitation-codes.*')) }}">
            Codes d'invitation
        </a>
        <a href="{{ route('admin.restricted-missions.index') }}" class="pb-1 {{ $link(request()->routeIs('admin.restricted-missions.*')) }}">
            Postes restreints
        </a>
    </nav>
    @include('partials.logout-button')
</div>
