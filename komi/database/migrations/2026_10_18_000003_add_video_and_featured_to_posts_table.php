<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_pinned');
            $table->string('video_provider', 20)->nullable()->after('link_url');
            $table->string('video_id', 64)->nullable()->after('video_provider');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'video_provider', 'video_id']);
        });
    }
};
