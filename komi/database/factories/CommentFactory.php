<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Por defecto genera un comentario principal (`parent_id = null`); usa el
     * estado [replyTo] para crear respuestas anidadas dentro del mismo post.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'parent_id' => null, // Comentario principal (raíz del hilo)
            'content' => fake()->optional(0.2)->sentence()
                ?? fake()->paragraph(1),
        ];
    }

    /**
     * Respuesta anidada: se vincula a un comentario existente del mismo post.
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn () => [
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Fija el usuario autor del comentario.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /**
     * Fija la publicación a la que pertenece.
     */
    public function underPost(Post $post): static
    {
        return $this->state(fn () => ['post_id' => $post->id]);
    }
}