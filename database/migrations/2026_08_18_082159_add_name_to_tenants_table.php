<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `name` était porté par la colonne JSON `data` (HasDataColumn de stancl),
     * ce qui empêche les requêtes SQL directes (recherche console, tests).
     * Le tenant porte bien un nom métier (CdC § 4.2, plan-sprint0 § 5.7).
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
