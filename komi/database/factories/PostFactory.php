<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paragraphs = fake()->paragraphs(fake()->numberBetween(1, 3));
        $content = implode("\n\n", $paragraphs);

        if (fake()->boolean(30)) {
            $content = '## '.fake()->sentence()."\n\n".$content;
        }

        if (fake()->boolean(20)) {
            $content .= "\n\n**".fake()->catchPhrase().'**';
        }

        return [
            'user_id' => User::factory(),
            'community_id' => null,
            'content' => $content,
            'type' => 'text',
            'image_url' => null,
            'likes_count' => fake()->numberBetween(0, 250),
            'comments_count' => fake()->numberBetween(0, 40),
        ];
    }

    /**
     * Publicación de texto plano / Markdown.
     */
    public function text(): static
    {
        return $this->state([
            'type' => 'text',
            'image_url' => null,
        ]);
    }

    /**
     * Publicación con imagen.
     */
    public function withImage(): static
    {
        return $this->state([
            'type' => 'image',
            'image_url' => fake()->randomElement([
                'https://picsum.photos/seed/komi-feed-1/800/600',
                'https://picsum.photos/seed/komi-feed-2/800/600',
                'https://picsum.photos/seed/komi-feed-3/800/600',
                'https://picsum.photos/seed/komi-feed-4/800/600',
                'https://picsum.photos/seed/komi-feed-5/800/600',
                'https://picsum.photos/800/600',
            ]),
        ]);
    }

    /**
     * Publicación sin imagen adjunta.
     */
    public function withoutImage(): static
    {
        return $this->state(['image_url' => null]);
    }

    /**
     * Publicación con encuesta (poll). Crea las opciones después del post.
     */
    public function poll(): static
    {
        return $this->state([
            'type' => 'poll',
            'image_url' => null,
        ]);
    }
}
