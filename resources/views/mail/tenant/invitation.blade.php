<x-mail::message>
# Bonjour {{ $managerName }},

La boutique **{{ $shopName }}** vous attend sur Cakeman. Choisissez un mot de passe pour activer votre compte Gérant et commencer à travailler.

<x-mail::button :url="$acceptUrl">
    Activer ma boutique
</x-mail::button>

Ce lien est valable jusqu'au **{{ $expiresAt->format('d/m/Y H:i') }}** (48 heures). Si vous n'êtes pas à l'origine de cette boutique, vous pouvez ignorer cet e-mail.

Merci,<br>
L'équipe Cakeman
</x-mail::message>
