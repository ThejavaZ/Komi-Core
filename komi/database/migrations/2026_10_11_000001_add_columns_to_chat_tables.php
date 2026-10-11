<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('type', 20)->default('direct')->after('id');
            $table->string('title')->nullable()->after('type');
            $table->timestamp('last_message_at')->nullable();
            $table->index('last_message_at');
        });

        Schema::table('conversation_users', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamp('read_at')->nullable();
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversation_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['content', 'read_at']);
        });

        Schema::table('conversation_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversation_id');
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['type', 'title', 'last_message_at']);
        });
    }
};
