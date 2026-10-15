<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Sala de chat temática oficial vinculada a una comunidad.
            $table->foreignId('community_id')
                ->nullable()
                ->after('title')
                ->constrained('communities')
                ->nullOnDelete();
            $table->index('community_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('community_id');
        });
    }
};
