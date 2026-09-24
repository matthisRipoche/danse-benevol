<x-mail::message>
# Bienvenue au Salon de la Danse !

Ta candidature a été retenue. Pour créer ton compte bénévole, utilise le code d'invitation ci-dessous :

<x-mail::panel>
{{ $code }}
</x-mail::panel>

<x-mail::button :url="$registerUrl">
Créer mon compte
</x-mail::button>

Ce code est personnel, à usage unique et valable jusqu'au {{ $expiresAt->format('d/m/Y à H:i') }}.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
