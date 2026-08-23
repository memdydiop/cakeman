<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_dunning_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt');
            $table->string('status')->default('failed'); // failed | recovered | suspended
            $table->string('last_error')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'attempt']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_dunning_attempts');
    }
};
