<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La suppression d'un utilisateur console doit préserver l'historique
     * d'audit : la colonne devient nullable et la FK passe en ON DELETE
     * SET NULL.
     */
    public function up(): void
    {
        Schema::table('admin_audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id')->nullable()->change();
        });

        DB::statement('ALTER TABLE admin_audit_logs DROP CONSTRAINT admin_audit_logs_admin_user_id_foreign');
        DB::statement('ALTER TABLE admin_audit_logs ADD CONSTRAINT admin_audit_logs_admin_user_id_foreign FOREIGN KEY (admin_user_id) REFERENCES admin_users (id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE admin_audit_logs DROP CONSTRAINT admin_audit_logs_admin_user_id_foreign');
        DB::statement('ALTER TABLE admin_audit_logs ADD CONSTRAINT admin_audit_logs_admin_user_id_foreign FOREIGN KEY (admin_user_id) REFERENCES admin_users (id)');

        Schema::table('admin_audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id')->nullable(false)->change();
        });
    }
};
