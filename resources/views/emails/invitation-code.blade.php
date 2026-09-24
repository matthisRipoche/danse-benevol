<x-mail::message>
@if ($isReturningVolunteer)
# Content de te revoir !

Ta candidature pour **{{ $editionName }}** a été retenue. Connecte-toi avec ton compte habituel, puis utilise le code d'invitation ci-dessous pour rejoindre cette édition :
@else
# Bienvenue au Salon de la Danse !

Ta candidature a été retenue. Pour créer ton compte bénévole, utilise le code d'invitation ci-dessous :
@endif

<x-mail::panel>
{{ $code }}
</x-mail::panel>

<x-mail::button :url="$actionUrl">
{{ $isReturningVolunteer ? "Rejoindre l'édition" : 'Créer mon compte' }}
</x-mail::button>

Ce code est personnel, à usage unique et valable jusqu'au {{ $expiresAt->format('d/m/Y à H:i') }}.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
