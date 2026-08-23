<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paramétrage global du tenant (CdC § 4.3) — socle minimal Sprint 0 :
     * la structure complète (onglets Branding/Commercial/Atelier/Livraison/
     * Tarifs/Intégrations/Stock, Module 2) arrive avec le Sprint 1.
     */
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('slogan')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->smallInteger('deposit_default_percent')->default(50);
            $table->string('cancellation_policy')->default('intermediate');
            $table->integer('daily_cake_quota')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
