<?php

use App\Enums\AdminPermission;
use App\Models\AdminWebhookLog;
use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Monitoring')] class extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::MonitoringView->value, 'admin'), 403);
    }

    #[Computed]
    public function pendingJobs(): int
    {
        return (int) DB::table('jobs')->count();
    }

    #[Computed]
    public function failedJobs(): int
    {
        return (int) DB::table('failed_jobs')->count();
    }

    /** @return array<string, int> */
    #[Computed]
    public function webhooksLast24h(): array
    {
        return AdminWebhookLog::query()
            ->where('created_at', '>=', now()->subHours(24))
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (int $total): int => $total)
            ->all();
    }

    /** @return array<string, string> */
    public function webhookStatusLabels(): array
    {
        return [
            'received' => 'Reçu',
            'processed' => 'Traité',
            'ignored' => 'Ignoré',
            'duplicate' => 'Doublon',
            'mismatch' => 'Écart',
            'failed' => 'Échec',
        ];
    }

    #[Computed]
    public function failedJobList(): LengthAwarePaginator
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function tenantCount(): int
    {
        return (int) Cache::remember('monitoring.tenantCount', 60, fn (): int => Tenant::count());
    }

    #[Computed]
    public function storageUsage(): string
    {
        return Cache::remember('monitoring.storageUsage', 60, function (): string {
            try {
                $bytes = collect(Storage::disk('local')->allFiles())->sum(fn (string $f): int => Storage::disk('local')->size($f));
                if ($bytes > 1_073_741_824) {
                    return round($bytes / 1_073_741_824, 2).' Go';
                }
                if ($bytes > 1_048_576) {
                    return round($bytes / 1_048_576, 1).' Mo';
                }

                return round($bytes / 1024, 1).' Ko';
            } catch (Throwable) {
                return '—';
            }
        });
    }

    #[Computed]
    public function backupCount(): int
    {
        return (int) Cache::remember('monitoring.backupCount', 60, fn (): int => count(Storage::disk('local')->files('exports')));
    }

    #[Computed]
    public function uptime(): string
    {
        return Cache::remember('monitoring.uptime', 60, function (): string {
            try {
                $start = Carbon::createFromTimestamp((int) filemtime(storage_path('framework/cache/data')));

                return $start->diffForHumans(short: true);
            } catch (Throwable) {
                return '—';
            }
        });
    }

    public function jobPayloadSnippet(string $payload): string
    {
        $decoded = json_decode($payload, true);

        return $decoded['displayName'] ?? 'Job inconnu';
    }

    public function exceptionSnippet(string $exception): string
    {
        return Str::limit(Str::before($exception, "\n"), 160);
    }
};
