<x-mail::message>
# Invitation à rejoindre la console Cakeman

Vous avez été invité(e) en tant que **{{ $role }}** de la console d'administration.

{{-- Action Button --}}
<x-mail::button :url="$acceptUrl">
    Créer mon mot de passe
</x-mail::button>

Ce lien est valable jusqu'au **{{ $expiresAt->format('d/m/Y H:i') }}** (48 heures). Si vous ne l'avez pas demandé, vous pouvez ignorer cet e-mail.

Merci,<br>
L'équipe Cakeman
</x-mail::message>