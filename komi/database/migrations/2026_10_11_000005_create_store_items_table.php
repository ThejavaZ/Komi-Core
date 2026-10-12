<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('type', 30)->default('template'); // template | subscription | item
            $table->unsignedInteger('price')->default(0); // precio en coins
            $table->json('preview_data')->nullable(); // colores/YAML para la vista previa
            $table->string('status', 20)->default('active'); // active | archived
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_items');
    }
};
