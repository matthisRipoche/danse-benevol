<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Modifier {{ $volunteer->first_name }} {{ $volunteer->last_name }} — {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-sand-50 text-[#1e1e24] antialiased">
        @include('admin.partials.nav')

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <a href="{{ route('admin.volunteers.show', $volunteer) }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-brand-500">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Retour à la fiche
            </a>

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
                <section class="rounded-xl border border-sand-200 bg-white p-6 lg:col-span-2">
                    <h1 class="font-heading text-2xl font-bold">Modifier {{ $volunteer->first_name }} {{ $volunteer->last_name }}</h1>
                    <p class="mt-1 mb-5 text-sm text-stone-500">Les modifications sont enregistrées dans le journal d'audit.</p>

                    @include('admin.partials.flash')

                    <form method="POST" action="{{ route('admin.volunteers.update', $volunteer) }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                        @csrf
                        @method('PUT')

                        @include('partials.profile-fields', [
                            'user' => $volunteer,
                            'photoUrl' => $volunteer->photo_path ? route('admin.volunteers.photo', $volunteer) : null,
                            'showMinorField' => true,
                        ])

                        <button type="submit" class="h-11 w-full rounded-full bg-brand-500 px-6 font-semibold text-white shadow-sm hover:bg-brand-600 sm:w-fit">
                            Enregistrer
                        </button>
                    </form>
                </section>

                <aside class="rounded-xl border border-sand-200 bg-white p-6 text-sm text-stone-600">
                    <h2 class="mb-2 font-heading text-lg font-semibold text-[#1e1e24]">Bon à savoir</h2>
                    <ul class="flex list-inside list-disc flex-col gap-2">
                        <li>Tu peux modifier un profil même verrouillé par la validation du planning.</li>
                        <li>La nouvelle adresse e-mail devient l'identifiant de connexion du bénévole : préviens-le.</li>
                        <li>La photo remplacée est supprimée du serveur.</li>
                    </ul>
                </aside>
            </div>
        </main>
    </body>
</html>
