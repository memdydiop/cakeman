<div class="space-y-4">
    <x-page-header
        title="Monitoring"
        subtitle="Files d'attente, webhooks et erreurs d'exécution"
        :breadcrumbs="[['label' => 'Monitoring']]" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $webhooks = $this->webhooksLast24h;
            $processed = ($webhooks['processed'] ?? 0) + ($webhooks['received'] ?? 0) + ($webhooks['ignored'] ?? 0) + ($webhooks['duplicate'] ?? 0);
            $failed = ($webhooks['failed'] ?? 0) + ($webhooks['mismatch'] ?? 0);
        @endphp

        <x-widget icon="queue-list" color="blue" title="File d'attente" :value="$this->pendingJobs"
            :trend-text="'jobs à exécuter'" trend-color="text-blue-600" :href="route('admin.monitoring.index')" />

        <x-widget icon="exclamation-triangle" color="red" title="Jobs échoués" :value="$this->failedJobs"
            :trend-text="'depuis le début'" trend-color="text-red-600" :href="route('admin.monitoring.index')" />

        <x-widget icon="arrow-path" color="green" title="Webhooks — 24 h" :value="$processed"
            :trend-text="'traités'" trend-color="text-green-600" :href="route('admin.webhooks.index')" />

        <x-widget icon="shield-exclamation" color="amber" title="Webhooks en échec" :value="$failed"
            :trend-text="'sur 24 h'" trend-color="text-amber-600" :href="route('admin.webhooks.index')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-widget icon="building-storefront" color="indigo" title="Boutiques" :value="$this->tenantCount" trend-text="total" trend-color="text-indigo-600" :href="route('admin.tenants.index')" />
        <x-widget icon="circle-stack" color="zinc" title="Stockage local" :value="$this->storageUsage" trend-text="exports + logs" trend-color="text-zinc-500" />
        <x-widget icon="archive-box" color="amber" title="Backups / Exports" :value="$this->backupCount" trend-text="fichiers" trend-color="text-amber-600" :href="route('admin.exports.index')" />
        <x-widget icon="clock" color="green" title="Uptime cache" :value="$this->uptime" trend-text="depuis dernier clear" trend-color="text-green-600" />
    </div>

    <flux:card class="p-6 ">
        <x-card-header icon="exclamation-triangle" title="Derniers jobs échoués" subtitle="Erreurs d'exécution à corriger" />

        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Job</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">File</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Échec</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Erreur</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->failedJobList as $job)
                        <flux:table.row :key="$job->id" class="hover:bg-zinc-50/80">
                            <flux:table.cell class="whitespace-nowrap font-medium">{{ $this->jobPayloadSnippet($job->payload) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">{{ $job->queue }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="whitespace-nowrap text-sm tabular-nums">{{ \Illuminate\Support\Carbon::parse($job->failed_at)->format('d/m/Y H:i') }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-mono text-xs text-red-600">{{ $this->exceptionSnippet($job->exception) }}</span>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500">
                                {{ __('Aucun job échoué — la file est saine.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $this->failedJobList->links() }}
            </div>
        </x-card-body>
    </flux:card>
</div>