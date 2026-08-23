<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceExportService
{
    /**
     * @return Collection<int, Invoice>
     */
    public function query(?CarbonInterface $from, ?CarbonInterface $to, ?string $tenantId = null): Collection
    {
        return Invoice::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->with(['tenant', 'subscription.plan'])
            ->orderBy('created_at')
            ->get();
    }

    public function toCsv(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?string $tenantId = null): StreamedResponse
    {
        $invoices = $this->query($from, $to, $tenantId);

        $filename = 'invoices-'.($from?->format('Y-m-d') ?? 'all').'-'.($to?->format('Y-m-d') ?? 'all').'.csv';

        return response()->streamDownload(function () use ($invoices, $from, $to, $tenantId): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, ['number', 'tenant', 'domain', 'plan', 'amount_FCFA', 'status', 'due_at', 'paid_at', 'period_start', 'period_end', 'method']);

            foreach ($invoices as $inv) {
                $domain = $inv->tenant->domains->first();
                $domainName = $domain ? $domain->getAttribute('domain') : '';

                fputcsv($out, [
                    $inv->number,
                    $inv->tenant->name ?? $inv->tenant_id,
                    $domainName,
                    $inv->subscription?->plan->name ?? '',
                    $inv->amount,
                    $inv->status->value,
                    $inv->due_at?->format('Y-m-d'),
                    $inv->paid_at?->format('Y-m-d'),
                    $inv->period_start?->format('Y-m-d'),
                    $inv->period_end?->format('Y-m-d'),
                    $inv->method ?? '',
                ]);
            }

            foreach (CreditNote::query()
                ->when($from, function ($q) use ($from) {
                    return $q->where('created_at', '>=', $from);
                })
                ->when($to, function ($q) use ($to) {
                    return $q->where('created_at', '<=', $to);
                })
                ->when($tenantId, function ($q) use ($tenantId) {
                    return $q->where('tenant_id', $tenantId);
                })
                ->with('tenant')->orderBy('created_at')->cursor() as $cn) {
                /** @var CreditNote $cn */
                $tenantName = $cn->tenant->name ?? $cn->tenant_id;

                fputcsv($out, [
                    'AVOIR-'.$cn->id,
                    $tenantName,
                    '',
                    '',
                    -$cn->amount,
                    'credit_note',
                    '',
                    $cn->created_at->format('Y-m-d'),
                    '',
                    '',
                    '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function storeMonthly(CarbonInterface $month): string
    {
        $from = Carbon::parse($month)->startOfMonth();
        $to = Carbon::parse($month)->endOfMonth();
        $path = 'exports/'.$month->format('Y-m').'.csv';

        $invoices = $this->query($from, $to);

        $tmp = fopen('php://temp', 'r+');

        if ($tmp === false) {
            throw new \RuntimeException('Unable to open temp stream');
        }

        fputcsv($tmp, ['number', 'tenant', 'domain', 'plan', 'amount_FCFA', 'status', 'due_at', 'paid_at', 'period_start', 'period_end', 'method']);

        foreach ($invoices as $inv) {
            $domain = $inv->tenant->domains->first();
            $domainName = $domain ? $domain->getAttribute('domain') : '';

            fputcsv($tmp, [
                $inv->number, $inv->tenant->name ?? $inv->tenant_id,
                $domainName,
                $inv->subscription?->plan->name ?? '', $inv->amount,
                $inv->status->value, $inv->due_at?->format('Y-m-d'),
                $inv->paid_at?->format('Y-m-d'), $inv->period_start?->format('Y-m-d'),
                $inv->period_end?->format('Y-m-d'), $inv->method ?? '',
            ]);
        }

        rewind($tmp);
        $content = stream_get_contents($tmp);

        if ($content === false) {
            $content = '';
        }

        Storage::disk('local')->put($path, $content);
        fclose($tmp);

        return $path;
    }
}
