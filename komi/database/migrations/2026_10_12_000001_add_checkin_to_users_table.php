<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ultimo dia (fecha) en que el usuario reclamo la recompensa diaria.
            $table->date('last_checkin_at')->nullable()->after('coins');
            // Racha actual de dias consecutivos de check-in.
            $table->unsignedInteger('checkin_streak')->default(0)->after('last_checkin_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_checkin_at', 'checkin_streak']);
        });
    }
};
