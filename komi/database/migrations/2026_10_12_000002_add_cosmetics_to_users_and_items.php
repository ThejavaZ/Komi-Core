<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            // Clase de cosmético: frame | chat_theme | title (null = no cosmético).
            $table->string('cosmetic_type', 20)->nullable()->after('type');
        });

        Schema::table('users', function (Blueprint $table) {
            // Inventario activo del usuario (cosméticos comprados y aplicados).
            $table->foreignId('active_frame_id')
                ->nullable()
                ->constrained('store_items')
                ->nullOnDelete()
                ->after('coins');
            $table->foreignId('active_chat_theme_id')
                ->nullable()
                ->constrained('store_items')
                ->nullOnDelete()
                ->after('active_frame_id');
            $table->string('custom_title', 60)->nullable()->after('active_chat_theme_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_frame_id');
            $table->dropConstrainedForeignId('active_chat_theme_id');
            $table->dropColumn('custom_title');
        });

        Schema::table('store_items', function (Blueprint $table) {
            $table->dropColumn('cosmetic_type');
        });
    }
};
