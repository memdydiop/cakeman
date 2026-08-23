<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal des webhooks de paiement des abonnements (CdC § 4.2, Module 12) :
     * payload brut horodaté, signature vérifiée, statut de traitement,
     * rejeu manuel possible (mécanique Module 9).
     */
    public function up(): void
    {
        Schema::create('admin_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event', 60);
            $table->string('provider_ref', 120)->nullable()->index();
            $table->jsonb('payload');
            $table->boolean('signature_valid')->default(false);
            $table->string('status', 20)->default('received');
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_webhook_logs');
    }
};
