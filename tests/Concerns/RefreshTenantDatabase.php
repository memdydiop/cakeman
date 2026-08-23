<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;

/**
 * Alternative à `RefreshDatabase` pour le tenancy par schémas PostgreSQL :
 * les schémas `tenant_*` sont créés par DDL (CREATE SCHEMA) — non visible par
 * la connexion tenant si la transaction de RefreshDatabase n'a pas commité.
 *
 * Stratégie : `migrate:fresh` une fois par classe (sans transaction), puis
 * purge des tables centrales entre chaque test ; chaque test nettoie son
 * propre schéma tenant via son teardown.
 */
trait RefreshTenantDatabase
{
    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh');

            // Purge des schémas tenant orphelins d'exécutions précédentes.
            $schemas = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'tenant\\_%'");

            foreach ($schemas as $schema) {
                DB::statement('DROP SCHEMA IF EXISTS "'.$schema->schema_name.'" CASCADE');
            }

            RefreshDatabaseState::$migrated = true;
        }
    }

    protected function purgeCentralTables(): void
    {
        DB::statement('TRUNCATE TABLE '
            .'admin_audit_logs, '
            .'admin_login_logs, '
            .'admin_api_tokens, '
            .'announcements, '
            .'credit_notes, '
            .'coupons, '
            .'admin_user_invitations, '
            .'sessions, '
            .'model_has_roles, '
            .'model_has_permissions, '
            .'role_has_permissions, '
            .'roles, '
            .'permissions, '
            .'admin_users, '
            .'settings, '
            .'domains, '
            .'tenants, '
            .'landing_leads '
            .'RESTART IDENTITY CASCADE');
    }
}
