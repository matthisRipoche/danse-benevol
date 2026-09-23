@php
    $navLink = fn (bool $active) => $active
        ? 'bg-brand-500 text-white shadow-sm'
        : 'text-stone-600 hover:bg-sand-100 hover:text-brand-500';
@endphp
<header class="sticky top-0 z-30 border-b border-sand-200 bg-white/90 backdrop-blur print:hidden">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <div class="hidden min-w-0 flex-col sm:flex">
            <span class="truncate font-heading font-bold text-brand-500">Salon de la Danse</span>
            <span class="text-xs font-semibold tracking-wider text-stone-500 uppercase">Espace bénévole</span>
        </div>
        <nav class="flex items-center gap-1 rounded-full bg-sand-50 p-1 text-sm font-semibold" aria-label="Espace bénévole">
            <a href="{{ route('planning.index') }}" @class(['rounded-full px-3 py-1.5 sm:px-4', $navLink(request()->routeIs('planning.*'))])>Mon planning</a>
            <a href="{{ route('profile.show') }}" @class(['rounded-full px-3 py-1.5 sm:px-4', $navLink(request()->routeIs('profile.*'))])>Mon profil</a>
        </nav>
        <div class="flex shrink-0 items-center gap-3">
            <a href="{{ route('profile.show') }}" class="flex items-center gap-2 rounded-full hover:opacity-80" title="Mon profil">
                @if (auth()->user()->photo_path)
                    <img src="{{ route('profile.photo') }}" alt="" class="size-8 rounded-full object-cover ring-2 ring-sand-200">
                @else
                    <span class="flex size-8 items-center justify-center rounded-full bg-sand-100 text-xs font-bold text-brand-500 ring-2 ring-sand-200">
                        {{ mb_substr(auth()->user()->first_name, 0, 1) }}{{ mb_substr(auth()->user()->last_name, 0, 1) }}
                    </span>
                @endif
                <span class="hidden text-sm text-stone-600 lg:inline">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            </a>
            @include('partials.logout-button')
        </div>
    </div>
</header>
