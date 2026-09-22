<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="text-sm text-gray-500 hover:underline dark:text-gray-400">
        Déconnexion
    </button>
</form>
