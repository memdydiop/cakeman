<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('tenants:delete {tenant : ID ou nom du tenant à supprimer} {--force : Supprime sans confirmation}')]
#[Description('Supprime un tenant, ses domaines et sa base de données (schéma)')]
class TenantsDelete extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = Tenant::query();

        if (Str::isUuid($this->argument('tenant'))) {
            $query->where('id', $this->argument('tenant'));
        } else {
            $query->where('name', $this->argument('tenant'));
        }

        $tenant = $query->first();

        if (! $tenant) {
            $this->error('Tenant introuvable : '.$this->argument('tenant'));

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $confirmed = $this->confirm("Supprimer définitivement « {$tenant->name} » ({$tenant->id}) ainsi que sa base de données ?");

            if (! $confirmed) {
                $this->info('Suppression annulée.');

                return self::SUCCESS;
            }
        }

        $tenant->delete();

        $this->info("Tenant « {$tenant->name} » supprimé, base de données incluse.");

        return self::SUCCESS;
    }
}
