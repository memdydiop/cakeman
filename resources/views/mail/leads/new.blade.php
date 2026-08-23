<x-mail::message>
# Nouveau lead — {{ $lead->name }}

Un prospect vient de demander une démonstration depuis la landing.

| | |
| --- | --- |
| **Nom** | {{ $lead->name }} |
| **E-mail** | {{ $lead->email }} |
| **Téléphone** | {{ $lead->phone ?? '—' }} |
| **Boutique** | {{ $lead->shop_name ?? '—' }} |
| **Message** | {{ $lead->notes ?? '—' }} |

<x-mail::button :url="url('/admin/leads')">
    Voir les leads
</x-mail::button>

Merci,<br>
L'équipe Cakeman
</x-mail::message>