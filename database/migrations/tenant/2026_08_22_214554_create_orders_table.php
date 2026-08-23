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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending | confirmed | in_production | ready | delivered | cancelled
            $table->integer('subtotal')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('total')->default(0);
            $table->integer('deposit')->default(0);
            $table->integer('balance')->default(0);
            $table->timestamp('due_at')->nullable(); // retrait/livraison sur-mesure
            $table->string('occasion')->nullable(); // anniversaire, mariage…
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable(); // parfum, étages, texte
            $table->string('delivery_type')->nullable(); // retrait | livraison
            $table->string('delivery_address')->nullable();
            $table->string('payment_method')->nullable(); // especes | wave | orange_money | virement
            $table->string('delivery_photo_path')->nullable();
            $table->text('delivery_signature')->nullable(); // base64
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
