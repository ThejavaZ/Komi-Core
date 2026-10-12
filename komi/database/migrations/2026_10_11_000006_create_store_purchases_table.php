<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('store_items')->cascadeOnDelete();
            $table->unsignedInteger('price_paid');
            $table->timestamps();

            $table->index(['buyer_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_purchases');
    }
};
