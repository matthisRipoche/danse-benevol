<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Modifier mes informations — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('partials.volunteer-header')

        <main class="mx-auto max-w-xl px-4 py-6 sm:px-6 lg:py-8">
            <a href="{{ route('profile.show') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-brand-500">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Retour à mon profil
            </a>

            <section class="rounded-2xl border border-sand-200 bg-white p-5 shadow-sm sm:p-6">
                <h1 class="font-heading text-xl font-bold">Modifier mes informations</h1>
                <p class="mt-1 mb-5 text-sm text-stone-600">
                    Tu peux corriger tes informations jusqu'à la validation de ton planning. Elles seront ensuite verrouillées.
                </p>

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    @include('partials.profile-fields', [
                        'user' => $user,
                        'photoUrl' => $user->photo_path ? route('profile.photo') : null,
                        'showMinorField' => false,
                    ])

                    <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                        <a href="{{ route('profile.show') }}" class="flex h-11 items-center justify-center rounded-full px-5 font-semibold text-stone-600 hover:bg-sand-100">
                            Annuler
                        </a>
                        <button type="submit" class="h-11 rounded-full bg-brand-500 px-6 font-semibold text-white shadow-sm hover:bg-brand-600">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </body>
</html>
