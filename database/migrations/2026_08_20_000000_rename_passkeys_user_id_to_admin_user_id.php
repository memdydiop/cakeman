<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La table centrale `passkeys` référençait `admin_users` via une colonne
 * `user_id` : le trait PasskeyAuthenticatable génère la FK `admin_user_id`
 * à partir du nom du modèle. Renommage pour cohérence avec le modèle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passkeys', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->renameColumn('user_id', 'admin_user_id');
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('passkeys', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
            $table->renameColumn('admin_user_id', 'user_id');
            $table->foreign('user_id')->references('id')->on('admin_users')->cascadeOnDelete();
        });
    }
};
