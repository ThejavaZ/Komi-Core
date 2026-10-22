<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('ama_status', 10)->nullable()->after('type');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->boolean('is_answered')->default(false)->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('ama_status');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('is_answered');
        });
    }
};
