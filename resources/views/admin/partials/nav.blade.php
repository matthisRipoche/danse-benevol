<div class="mb-6 flex items-center justify-between">
    <nav class="flex items-center gap-4 text-sm">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'font-semibold' : 'underline' }}">
            Tableau de bord
        </a>
        <a href="{{ route('admin.volunteers.index') }}" class="{{ request()->routeIs('admin.volunteers.*') ? 'font-semibold' : 'underline' }}">
            Bénévoles
        </a>
        <a href="{{ route('admin.invitation-codes.index') }}" class="{{ request()->routeIs('admin.invitation-codes.*') ? 'font-semibold' : 'underline' }}">
            Codes d'invitation
        </a>
        <a href="{{ route('admin.restricted-missions.index') }}" class="{{ request()->routeIs('admin.restricted-missions.*') ? 'font-semibold' : 'underline' }}">
            Postes restreints
        </a>
    </nav>
    @include('partials.logout-button')
</div>
