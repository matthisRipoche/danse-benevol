<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Inscription — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center p-6">
            <div class="w-full max-w-md">
                <h1 class="mb-6 text-lg font-semibold">Créer mon compte bénévole</h1>

                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="code" class="mb-1 block text-sm font-medium">Code d'invitation</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $code) }}" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label for="first_name" class="mb-1 block text-sm font-medium">Prénom</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                        </div>
                        <div class="flex-1">
                            <label for="last_name" class="mb-1 block text-sm font-medium">Nom</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium">E-mail</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>

                    <div>
                        <label for="phone" class="mb-1 block text-sm font-medium">Téléphone</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium">Mot de passe</label>
                        <input type="password" name="password" id="password" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium">Confirmer le mot de passe</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>

                    <div>
                        <label for="photo" class="mb-1 block text-sm font-medium">Photo récente (obligatoire pour le badge)</label>
                        <input type="file" name="photo" id="photo" accept="image/*" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>

                    <button type="submit"
                        class="mt-2 rounded-md bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Créer mon compte
                    </button>
                </form>
            </div>
        </div>
    </body>
</html>
