<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_images', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->unsignedSmallInteger('position')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('post_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_id');
            $table->dropColumn(['url', 'position']);
        });
    }
};
