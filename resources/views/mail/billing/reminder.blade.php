<x-mail::message>
# Bonjour {{ $managerName }},

L'abonnement **{{ $planName }}** de votre boutique **{{ $shopName }}** arrive à échéance dans **{{ $daysLeft }} jour(s)** (le {{ $dueAt->format('d/m/Y') }}).

Montant à régler : **{{ number_format($amount, 0, ',', ' ') }} FCFA**. Le paiement se fait par Mobile Money.

Merci,<br>
L'équipe Cakeman
</x-mail::message>
