<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prospects de la landing (CdC § 13, acquisition) — dédoublonnage par
     * e-mail (index unique) et normalisation téléphone Côte d'Ivoire (+225).
     */
    public function up(): void
    {
        Schema::create('landing_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->index();
            $table->string('shop_name')->nullable();
            $table->string('plan_code')->nullable();
            $table->string('source')->default('landing')->index();
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->uuid('converted_tenant_id')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_leads');
    }
};
