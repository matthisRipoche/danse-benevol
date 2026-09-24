<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $edition->name }} — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <a href="{{ route('admin.editions.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-brand-500">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Retour aux éditions
            </a>

            <div class="max-w-xl rounded-xl border border-sand-200 bg-white p-6">
                <h1 class="mb-5 font-heading text-xl font-bold">{{ $edition->name }}</h1>

                @include('admin.partials.flash')

                <form method="POST" action="{{ route('admin.editions.update', $edition) }}" class="flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    @include('admin.editions.partials.fields', ['edition' => $edition])

                    <button type="submit" class="h-11 rounded-full bg-brand-500 font-semibold text-white shadow-sm hover:bg-brand-600 sm:w-fit sm:px-6">
                        Enregistrer
                    </button>
                </form>
            </div>
        </main>
    </body>
</html>
