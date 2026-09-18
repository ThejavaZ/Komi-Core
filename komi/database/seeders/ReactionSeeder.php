<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReactionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = User::query()->where('status', 'active')->get();
        $posts = Post::query()->whereNull('deleted_at')->get();

        if ($users->isEmpty() || $posts->isEmpty()) {
            return;
        }

        foreach ($posts as $post) {
            $reactionCount = rand(0, min($users->count(), 15));
            $likers = $users->random($reactionCount);

            foreach ($likers as $user) {
                $daysAgo = fake()->numberBetween(0, 13);
                $createdAt = now()->subDays($daysAgo)->subHours(fake()->numberBetween(0, 23));

                Reaction::query()->updateOrCreate(
                    ['user_id' => $user->id, 'post_id' => $post->id],
                    ['created_at' => $createdAt, 'updated_at' => $createdAt]
                );
            }
        }

        foreach ($posts as $post) {
            $post->update([
                'likes_count' => Reaction::query()
                    ->where('post_id', $post->id)
                    ->count(),
            ]);
        }
    }
}
