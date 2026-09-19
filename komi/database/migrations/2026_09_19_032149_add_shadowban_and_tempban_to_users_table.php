<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_shadowbanned')->default(false)->after('warnings_count');
            $table->timestamp('banned_until')->nullable()->after('is_shadowbanned');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_shadowbanned', 'banned_until']);
        });
    }
};
