<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('comment_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            $table->foreignId('message_id')
                ->nullable()
                ->after('conversation_id')
                ->constrained('messages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('message_id');
            $table->dropConstrainedForeignId('conversation_id');
        });
    }
};
