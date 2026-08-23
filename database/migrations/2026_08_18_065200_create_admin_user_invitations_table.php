<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invitations des utilisateurs plateforme (CdC § 5.8 du plan Sprint 0) :
     * lien one-time avec expiration 48 h, rôle proposé, acceptation horodatée.
     */
    public function up(): void
    {
        Schema::create('admin_user_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('role');
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_user_invitations');
    }
};
