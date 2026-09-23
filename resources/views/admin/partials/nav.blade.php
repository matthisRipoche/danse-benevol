@php
    $adminLinks = [
        ['route' => 'admin.dashboard', 'active' => ['admin.dashboard'], 'label' => 'Tableau de bord',
            'icon' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>'],
        ['route' => 'admin.volunteers.index', 'active' => ['admin.volunteers.*'], 'label' => 'Bénévoles',
            'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'],
        ['route' => 'admin.invitation-codes.index', 'active' => ['admin.invitation-codes.*'], 'label' => "Codes d'invitation",
            'icon' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>'],
        ['route' => 'admin.missions.index', 'active' => ['admin.missions.*', 'admin.schedule.*'], 'label' => 'Missions',
            'icon' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M12 11h4M12 16h4M8 11h.01M8 16h.01"/>'],
        ['route' => 'admin.restricted-missions.index', 'active' => ['admin.restricted-missions.*'], 'label' => 'Postes restreints',
            'icon' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'],
        ['route' => 'admin.exports.index', 'active' => ['admin.exports.*'], 'label' => 'Exports',
            'icon' => '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>'],
    ];
    $admin = auth()->user();
    $initials = mb_substr($admin->first_name, 0, 1).mb_substr($admin->last_name, 0, 1);
@endphp
<header class="sticky top-0 z-30 border-b border-sand-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('admin.dashboard') }}" class="flex shrink-0 items-center gap-2">
            <span class="font-heading font-bold text-brand-500">Salon de la Danse</span>
            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-bold tracking-wider text-brand-500 uppercase">Admin</span>
        </a>

        <nav class="hidden items-center gap-1 text-sm font-semibold xl:flex" aria-label="Administration">
            @foreach ($adminLinks as $link)
                @php($isActive = request()->routeIs(...$link['active']))
                <a href="{{ route($link['route']) }}" @if ($isActive) aria-current="page" @endif
                    @class([
                        'rounded-full px-3 py-1.5 whitespace-nowrap transition',
                        'bg-brand-500 text-white shadow-sm' => $isActive,
                        'text-stone-600 hover:bg-sand-100 hover:text-brand-500' => ! $isActive,
                    ])>{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="hidden shrink-0 items-center gap-2 xl:flex">
            <span class="flex size-8 items-center justify-center rounded-full bg-sand-100 text-xs font-bold text-brand-500" title="{{ $admin->first_name }} {{ $admin->last_name }}">{{ $initials }}</span>
            @include('partials.logout-button')
        </div>

        <details class="group xl:hidden" data-admin-menu>
            <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-full text-stone-700 hover:bg-sand-100 [&::-webkit-details-marker]:hidden" aria-label="Menu">
                <svg class="size-6 group-open:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg class="hidden size-6 group-open:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </summary>

            <div class="absolute inset-x-0 top-full max-h-[calc(100dvh-4rem)] overflow-y-auto border-b border-sand-200 bg-white shadow-lg">
                <nav class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3 sm:px-6 lg:px-8" aria-label="Administration">
                    @foreach ($adminLinks as $link)
                        @php($isActive = request()->routeIs(...$link['active']))
                        <a href="{{ route($link['route']) }}" @if ($isActive) aria-current="page" @endif
                            @class([
                                'flex items-center gap-3 rounded-lg px-3 py-3 font-semibold',
                                'bg-brand-50 text-brand-600' => $isActive,
                                'text-stone-700 hover:bg-sand-50' => ! $isActive,
                            ])>
                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $link['icon'] !!}</svg>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 border-t border-sand-100 px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sand-100 text-xs font-bold text-brand-500">{{ $initials }}</span>
                        <span class="truncate text-sm font-medium text-stone-700">{{ $admin->first_name }} {{ $admin->last_name }}</span>
                    </div>
                    @include('partials.logout-button')
                </div>
            </div>
        </details>
    </div>
</header>

<script>
    (() => {
        const menu = document.querySelector('[data-admin-menu]');

        document.addEventListener('click', (event) => {
            if (menu.open && ! menu.contains(event.target)) {
                menu.open = false;
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && menu.open) {
                menu.open = false;
                menu.querySelector('summary').focus();
            }
        });
    })();
</script>
