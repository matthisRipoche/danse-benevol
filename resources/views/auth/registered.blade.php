<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Bienvenue — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center p-6 text-center">
            <h1 class="mb-2 text-lg font-semibold">Bienvenue, {{ auth()->user()->first_name }} !</h1>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Ton compte bénévole a bien été créé.</p>
        </div>
    </body>
</html>
