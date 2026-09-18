<?php

namespace Database\Seeders;

use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(?int $count = 80): void
    {
        $users = User::query()->where('status', 'active')->get();
        $communities = \App\Models\Community::all();

        if ($users->isEmpty()) {
            return;
        }

        $allTags = Tag::all();
        $textCount = (int) round($count * 0.40);
        $imageCount = (int) round($count * 0.40);
        $pollCount = $count - $textCount - $imageCount;

        // Text posts (40%)
        for ($i = 0; $i < $textCount; $i++) {
            $daysAgo = fake()->numberBetween(0, 13);
            $createdAt = now()->subDays($daysAgo)->subHours(fake()->numberBetween(0, 23));

            $post = Post::factory()->text()->create([
                'user_id' => $users->random()->id,
                'community_id' => $communities->random()?->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $this->attachRandomTags($post, $allTags);
        }

        // Image posts (40%)
        for ($i = 0; $i < $imageCount; $i++) {
            $daysAgo = fake()->numberBetween(0, 13);
            $createdAt = now()->subDays($daysAgo)->subHours(fake()->numberBetween(0, 23));

            $post = Post::factory()->withImage()->create([
                'user_id' => $users->random()->id,
                'community_id' => $communities->random()?->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $this->attachRandomTags($post, $allTags);
        }

        // Poll posts (20%) with simulated votes
        for ($i = 0; $i < $pollCount; $i++) {
            $daysAgo = fake()->numberBetween(0, 13);
            $createdAt = now()->subDays($daysAgo)->subHours(fake()->numberBetween(0, 23));

            $post = Post::factory()->poll()->create([
                'user_id' => $users->random()->id,
                'community_id' => $communities->random()?->id,
                'content' => fake()->sentence(8).'?',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $this->attachRandomTags($post, $allTags);

            $optionCount = fake()->numberBetween(2, 4);
            $options = [];
            for ($o = 0; $o < $optionCount; $o++) {
                $options[] = PollOption::create([
                    'post_id' => $post->id,
                    'option_text' => fake()->unique()->word().fake()->optional(0.5)->word(),
                    'votes_count' => 0,
                ]);
            }

            $voters = $users->random(min(5, $users->count()));
            foreach ($voters as $voter) {
                $randomOption = $options[array_rand($options)];
                PollVote::create([
                    'user_id' => $voter->id,
                    'poll_option_id' => $randomOption->id,
                ]);
                $randomOption->increment('votes_count');
            }
        }
    }

    private function attachRandomTags(Post $post, $allTags): void
    {
        if ($allTags->isEmpty()) {
            return;
        }

        $tagCount = fake()->numberBetween(0, min(3, $allTags->count()));
        if ($tagCount > 0) {
            $randomTags = $allTags->random($tagCount);
            $post->tags()->sync($randomTags->pluck('id')->toArray());
        }
    }
}
