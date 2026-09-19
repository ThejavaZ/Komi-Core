<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Posts: expand type to support all content types ────────────────
        DB::statement("ALTER TABLE posts RENAME COLUMN type TO type_old");
        Schema::table('posts', function (Blueprint $table) {
            $table->string('type', 20)->default('text')->after('content');
            $table->text('link_url')->nullable()->after('image_url');
        });

        // Migrate existing data
        DB::statement("UPDATE posts SET type = type_old");
        DB::statement("UPDATE posts SET type = 'image' WHERE type_old = 'image'");

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('type_old');
        });

        // ── Quizzes ──────────────────────────────────────────────────────
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->boolean('show_results_immediately')->default(false);
            $table->boolean('allow_retake')->default(true);
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->text('question');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->string('answer_text', 500);
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->unsignedInteger('time_taken_seconds')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'quiz_id']);
        });

        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_answer_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // ── Wikis ────────────────────────────────────────────────────────
        Schema::create('wikis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('cover_image')->nullable();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('last_editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique('slug');
        });

        Schema::create('wiki_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('content');
            $table->text('edit_summary')->nullable();
            $table->timestamps();

            $table->unique(['wiki_id', 'version']);
        });

        // ── Questions (Q&A) ──────────────────────────────────────────────
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accepted_answer_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->boolean('is_solved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
        Schema::dropIfExists('wiki_versions');
        Schema::dropIfExists('wikis');
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('link_url');
        });
    }
};
