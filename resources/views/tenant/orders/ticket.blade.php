<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ticket #{{ $order->id }} — {{ $order->client?->name }}</title>
    <style>
        @media print { .no-print { display:none; } }
        body { font-family: ui-sans-serif, system-ui; color:#18181b; max-width: 720px; margin: 2rem auto; padding: 1rem; }
        h1 { font-size: 1.4rem; font-weight: 700; }
        .muted { color:#71717a; font-size:.85rem; }
        table { width:100%; border-collapse: collapse; margin-top:1rem; }
        th, td { border:1px solid #e4e4e7; padding:.5rem; text-align:left; font-size:.9rem; }
        th { background:#fafafa; }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="text-align:right; margin-bottom:1rem;">
        <button onclick="window.print()" style="padding:.5rem 1rem; border:1px solid #e4e4e7; border-radius:.5rem;">Imprimer</button>
        <a href="{{ route('tenant.orders.index') }}" style="margin-left:.5rem; font-size:.85rem;">Retour</a>
    </div>

    <h1>Commande #{{ $order->id }} — {{ $order->client?->name ?? 'Sans client' }}</h1>
    <div class="muted">
        Échéance {{ $order->due_at?->format('d/m/Y H:i') ?? '—' }} · {{ $order->delivery_type ?? 'retrait' }} {{ $order->delivery_address ? '— '.$order->delivery_address : '' }} · {{ $order->occasion ?? '' }}
    </div>
    @if ($order->notes)<div class="muted" style="margin-top:.5rem;">Notes: {{ $order->notes }}</div>@endif

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Qté</th>
                <th>Détails</th>
                <th style="text-align:right;">Prix</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                @php $c = $item->customization ?? []; @endphp
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>
                        @if ($c)
                            @foreach (collect($c)->except('etages') as $k => $v)
                                @if ($v)<div><span class="muted">{{ $k }}:</span> {{ is_array($v) ? json_encode($v) : $v }}</div>@endif
                            @endforeach
                            @if (!empty($c['etages']))
                                <div class="muted" style="margin-top:.25rem;">Étages:</div>
                                @foreach ($c['etages'] as $idx => $etage)
                                    <div class="muted">N{{ $idx+1 }} — {{ collect($etage)->filter()->map(fn($v,$k)=> $k.':'.$v)->implode(', ') }} @if ($etage['faux'] ?? false) <span style="color:#dc2626;">(faux)</span>@endif</div>
                                @endforeach
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td style="text-align:right;">{{ number_format($item->line_total,0,',',' ') }} FCFA</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="3" style="text-align:right;">Sous-total</td><td style="text-align:right; font-weight:600;">{{ number_format($order->subtotal,0,',',' ') }} FCFA</td></tr>
            @if ($order->discount>0)<tr><td colspan="3" style="text-align:right;">Remise</td><td style="text-align:right; color:#059669;">-{{ number_format($order->discount,0,',',' ') }} FCFA</td></tr>@endif
            <tr><td colspan="3" style="text-align:right; font-weight:700;">Total</td><td style="text-align:right; font-weight:700;">{{ number_format($order->total,0,',',' ') }} FCFA</td></tr>
            <tr><td colspan="3" style="text-align:right;">Acompte</td><td style="text-align:right;">{{ number_format($order->deposit,0,',',' ') }} FCFA</td></tr>
            <tr><td colspan="3" style="text-align:right; font-weight:700;">Reste à payer</td><td style="text-align:right; font-weight:700;">{{ number_format($order->balance,0,',',' ') }} FCFA</td></tr>
        </tfoot>
    </table>

    <div class="muted" style="margin-top:1rem;">Paiement: {{ $order->payment_method ?? '—' }} · Statut: {{ $order->status }}</div>

    @if ($order->status === 'delivered' && ($order->delivery_photo_path || $order->delivery_signature))
        <div style="margin-top:1.5rem; border-top:1px solid #e4e4e7; padding-top:1rem;">
            <h3 style="font-weight:600;">Preuve de livraison</h3>
            @if ($order->delivery_photo_path)<div style="margin-top:.5rem;"><img src="{{ Storage::disk('public')->url($order->delivery_photo_path) }}" style="max-width:320px; border:1px solid #e4e4e7; border-radius:.5rem;"></div>@endif
            @if ($order->delivery_signature)<div style="margin-top:.5rem;"><img src="{{ $order->delivery_signature }}" style="max-width:320px; border:1px solid #e4e4e7; background:white;"></div>@endif
            <div class="muted">Livré le {{ $order->delivered_at?->format('d/m/Y H:i') }}</div>
        </div>
    @endif
</body>
</html>
