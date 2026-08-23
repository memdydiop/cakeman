<?php

namespace App\Jobs;

use App\Models\Tenant;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Préparation du schéma tenant à sa création (pipeline `TenantCreated`) :
 * seed RBAC tenant (4 rôles racines) + ligne `tenant_settings` par défaut.
 */
class PrepareTenant implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tenant $tenant)
    {
        //
    }

    public function handle(): void
    {
        $this->tenant->run(function () {
            DB::table('tenant_settings')->insert([
                'business_name' => $this->tenant->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            (new TenantRolesSeeder)->run();
        });
    }
}
