<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name', 60);
            $table->string('description', 255)->nullable();
            $table->string('icon', 40)->nullable();
            $table->string('color', 9)->default('#673AB7');
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at')->useCurrent();
            $table->unique(['user_id', 'badge_id']);
        });

        // Badges base del sistema (siempre disponibles en cualquier entorno).
        DB::table('badges')->insert([
            [
                'key' => 'first_post',
                'name' => 'Primer Post',
                'description' => 'Publicó su primera publicación.',
                'icon' => 'edit_note',
                'color' => '#673AB7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'commenter',
                'name' => 'Conversador',
                'description' => 'Escribió 10 o más comentarios.',
                'icon' => 'chat_bubble',
                'color' => '#3F51B5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'popular_post',
                'name' => 'Post Popular',
                'description' => 'Una publicación suya llegó a 10 likes.',
                'icon' => 'favorite',
                'color' => '#E91E63',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'community_founder',
                'name' => 'Fundador',
                'description' => 'Creó su primera comunidad.',
                'icon' => 'groups',
                'color' => '#009688',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
