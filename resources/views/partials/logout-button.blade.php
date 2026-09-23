<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-sand-100 px-3 py-1.5 text-sm font-semibold text-stone-700 hover:bg-sand-200">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        <span class="sr-only sm:not-sr-only">Déconnexion</span>
    </button>
</form>
