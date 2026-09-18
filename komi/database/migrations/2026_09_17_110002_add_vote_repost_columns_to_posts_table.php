<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->integer('vote_score')->default(0)->after('comments_count');
            $table->unsignedInteger('reposts_count')->default(0)->after('vote_score');
            $table->foreignId('original_post_id')->nullable()->after('reposts_count')->constrained('posts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['vote_score', 'reposts_count', 'original_post_id']);
        });
    }
};
