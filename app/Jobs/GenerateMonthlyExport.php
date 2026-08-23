<?php

namespace App\Jobs;

use App\Models\AdminAuditLog;
use App\Services\InvoiceExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class GenerateMonthlyExport implements ShouldQueue
{
    use Queueable;

    public function handle(InvoiceExportService $service): void
    {
        $month = Carbon::now()->subMonth();
        $path = $service->storeMonthly($month);

        AdminAuditLog::record('invoice.exported', ['path' => $path, 'month' => $month->format('Y-m')], null);
    }
}
