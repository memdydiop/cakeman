<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->index('code');
            $table->index('is_active');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->index('tenant_id');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->index('published_at');
        });

        Schema::table('admin_api_tokens', function (Blueprint $table) {
            $table->index('token');
        });

        Schema::table('admin_login_logs', function (Blueprint $table) {
            $table->index(['email', 'success']);
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
        });

        Schema::table('admin_api_tokens', function (Blueprint $table) {
            $table->dropIndex(['token']);
        });

        Schema::table('admin_login_logs', function (Blueprint $table) {
            $table->dropIndex(['email', 'success']);
        });
    }
};
