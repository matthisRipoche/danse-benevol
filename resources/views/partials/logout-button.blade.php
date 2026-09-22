<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="rounded-md bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200">
        Déconnexion
    </button>
</form>
