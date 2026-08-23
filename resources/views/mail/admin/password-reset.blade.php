<x-mail::message>
# Réinitialisation de votre mot de passe

Un administrateur a demandé la réinitialisation de votre mot de passe d'accès à la console Cakeman (**{{ $email }}**).

<x-mail::button :url="$resetUrl">
    Choisir un nouveau mot de passe
</x-mail::button>

Ce lien est valable **60 minutes**. Si vous n'êtes pas à l'origine de cette demande, contactez un administrateur.

Merci,<br>
L'équipe Cakeman
</x-mail::message>