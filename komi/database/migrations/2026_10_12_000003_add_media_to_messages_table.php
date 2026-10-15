<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Tipo de mensaje: text | image | gif | sticker.
            $table->string('type', 20)->default('text')->after('conversation_id');
            // URL publica del medio adjunto (imagen/gif) o del sticker.
            $table->string('media_url', 2048)->nullable()->after('content');
            // Metadatos del sticker/emoji personalizado (etiqueta, pack, etc).
            $table->json('sticker_meta')->nullable()->after('media_url');

            // Un mensaje puede ser solo multimedia, sin texto.
            $table->text('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'media_url', 'sticker_meta']);
            $table->text('content')->nullable(false)->change();
        });
    }
};
