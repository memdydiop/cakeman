<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relances d'échéance déjà envoyées (J-7/J-3/J-1) — évite les doublons
     * entre deux exécutions du rattrapage quotidien.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->json('reminders_sent')->default('[]')->after('grace_until');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('reminders_sent');
        });
    }
};
