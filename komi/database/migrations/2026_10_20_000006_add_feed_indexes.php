<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Feed del perfil: WHERE user_id = ? ORDER BY created_at DESC.
            $table->index(['user_id', 'created_at'], 'posts_user_created_idx');
            // Feed de comunidad: WHERE community_id = ? ORDER BY created_at DESC.
            $table->index(['community_id', 'created_at'], 'posts_community_created_idx');
            // Feed por tipo (galería, video, ama…): WHERE type = ? ORDER BY created_at DESC.
            $table->index(['type', 'created_at'], 'posts_type_created_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            // Comentarios de un usuario (perfil / moderación).
            $table->index(['user_id', 'created_at'], 'comments_user_created_idx');
        });

        Schema::table('reactions', function (Blueprint $table) {
            // withExists likes del feed + evitar duplicados.
            $table->index(['user_id', 'post_id'], 'reactions_user_post_idx');
            $table->index('post_id', 'reactions_post_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            // Chat: últimos mensajes de una conversación.
            $table->index(['conversation_id', 'created_at'], 'messages_conversation_created_idx');
        });

        Schema::table('followers', function (Blueprint $table) {
            // Listas de seguidores/seguidos.
            $table->index('follower_id', 'followers_follower_idx');
            $table->index('following_id', 'followers_following_idx');
        });

        Schema::table('post_tags', function (Blueprint $table) {
            // Posts de un tag (TagPostsScreen) y tags de un post.
            $table->index('tag_id', 'post_tags_tag_idx');
            $table->index('post_id', 'post_tags_post_idx');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            // Guardados del usuario.
            $table->index('user_id', 'bookmarks_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_user_created_idx');
            $table->dropIndex('posts_community_created_idx');
            $table->dropIndex('posts_type_created_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_user_created_idx');
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex('reactions_user_post_idx');
            $table->dropIndex('reactions_post_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_created_idx');
        });

        Schema::table('followers', function (Blueprint $table) {
            $table->dropIndex('followers_follower_idx');
            $table->dropIndex('followers_following_idx');
        });

        Schema::table('post_tags', function (Blueprint $table) {
            $table->dropIndex('post_tags_tag_idx');
            $table->dropIndex('post_tags_post_idx');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropIndex('bookmarks_user_idx');
        });
    }
};
