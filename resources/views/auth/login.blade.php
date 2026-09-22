<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Connexion — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center p-6">
            <div class="w-full max-w-md">
                <h1 class="mb-6 text-lg font-semibold">Connexion</h1>

                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium">E-mail</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium">Mot de passe</label>
                        <input type="password" name="password" id="password" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>

                    <button type="submit"
                        class="mt-2 rounded-md bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Se connecter
                    </button>
                </form>

                @if (app()->environment('local'))
                    <form method="POST" action="{{ route('dev-login.admin') }}" class="mt-6 border-t border-dashed border-gray-300 pt-4 dark:border-gray-700">
                        @csrf
                        <button type="submit"
                            class="w-full rounded-md border border-dashed border-gray-400 px-5 py-2 text-sm text-gray-500 hover:border-gray-600 hover:text-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-200">
                            Connexion rapide admin (dev uniquement)
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </body>
</html>
