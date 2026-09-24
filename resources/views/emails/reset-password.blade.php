<x-mail::message>
# Bonjour {{ $firstName }},

Tu as demandé à réinitialiser le mot de passe de ton compte bénévole. Clique sur le bouton ci-dessous pour en choisir un nouveau :

<x-mail::button :url="$resetUrl">
Choisir un nouveau mot de passe
</x-mail::button>

Ce lien est valable {{ $expireMinutes }} minutes et ne fonctionne qu'une fois.

Si tu n'es pas à l'origine de cette demande, ignore cet e-mail : ton mot de passe actuel reste inchangé.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
