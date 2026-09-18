<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(?int $targetTotal = 200): void
    {
        $users = User::query()->where('status', 'active')->get();
        $posts = Post::query()->whereNull('deleted_at')->get();

        if ($users->isEmpty() || $posts->isEmpty()) {
            return;
        }

        $rootCount = (int) round($targetTotal * 0.6);
        $rootsByPost = $this->distribute($posts, $rootCount);

        $allComments = collect();

        foreach ($rootsByPost as $postId => $count) {
            $post = $posts->firstWhere('id', $postId);

            for ($i = 0; $i < $count; $i++) {
                $daysAgo = fake()->numberBetween(0, 13);
                $createdAt = now()->subDays($daysAgo)->subHours(fake()->numberBetween(0, 23));

                $allComments->push(Comment::factory()->create([
                    'user_id' => $users->random()->id,
                    'post_id' => $postId,
                    'parent_id' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]));
                $post->increment('comments_count');
            }
        }

        $repliesCount = $targetTotal - $rootCount;
        $repliesCreated = 0;

        while ($repliesCreated < $repliesCount && $allComments->isNotEmpty()) {
            $parent = $allComments->random();

            $daysAgo = fake()->numberBetween(0, 13);
            $createdAt = now()->subDays($daysAgo)->subHours(fake()->numberBetween(0, 23));

            $created = Comment::factory()
                ->replyTo($parent)
                ->create([
                    'user_id' => $users->random()->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

            $allComments->push($created);
            $post = $posts->firstWhere('id', $parent->post_id);
            $post?->increment('comments_count');

            $repliesCreated++;
        }
    }

    private function distribute($posts, int $total): array
    {
        $result = [];

        foreach ($posts as $post) {
            $result[$post->id] = 0;
        }

        $postIds = $posts->pluck('id')->all();
        for ($i = 0; $i < $total; $i++) {
            $result[$postIds[array_rand($postIds)]]++;
        }

        return $result;
    }
}
