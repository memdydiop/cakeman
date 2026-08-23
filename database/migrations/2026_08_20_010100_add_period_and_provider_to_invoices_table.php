<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Facturation des abonnements (CdC § 4.2, Module 12) : période couverte,
     * méthode Mobile Money et `provider_ref` unique (idempotence webhook).
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->string('method', 30)->nullable();
            $table->string('provider_ref', 120)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['provider_ref']);
            $table->dropColumn(['period_start', 'period_end', 'method', 'provider_ref']);
        });
    }
};
