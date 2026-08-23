<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sessions support temporaires (CdC § 12, Module 12) : ouverture par le
     * Super-Admin ou le Support sur le schéma d'un tenant, motif obligatoire,
     * durée limitée, expiration automatique — accès en lecture seule.
     */
    public function up(): void
    {
        Schema::create('admin_support_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('reason', 500);
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_support_sessions');
    }
};
