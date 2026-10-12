<?php

namespace Tests\Feature;

use App\Mail\SendOtpMail;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint4HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_write_rate_limit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (range(1, 10) as $i) {
            $this->postJson('/api/posts', ['content' => "Post {$i}"])->assertCreated();
        }

        $this->postJson('/api/posts', ['content' => 'Once'])->assertStatus(429);
    }

    public function test_likes_rate_limit(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $liker = User::factory()->create();
        Sanctum::actingAs($liker);

        foreach (range(1, 30) as $i) {
            $this->postJson("/api/posts/{$post->id}/like")->assertOk();
        }

        $this->postJson("/api/posts/{$post->id}/like")->assertStatus(429);
    }

    public function test_messages_rate_limit_per_conversation(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$me->id, $other->id]);

        Sanctum::actingAs($me);

        foreach (range(1, 30) as $i) {
            $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => "m{$i}"])
                ->assertCreated();
        }

        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'overflow'])
            ->assertStatus(429);
    }

    public function test_register_daily_ip_limit(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/register', [
                'name' => "User {$i}",
                'username' => "user{$i}",
                'email' => "user{$i}@example.com",
                'password' => 'secret123',
            ])->assertCreated();
        }

        // 6ta cuenta desde la misma IP el mismo dia → tope diario.
        $this->postJson('/api/register', [
            'name' => 'User 6',
            'username' => 'user6',
            'email' => 'user6@example.com',
            'password' => 'secret123',
        ])->assertStatus(429);
    }

    public function test_resend_otp_sends_mail_for_pending_user(): void
    {
        Mail::fake();
        $user = User::factory()->create(['status' => 'pending', 'email_verified_at' => null]);

        $this->postJson('/api/resend-otp', ['email' => $user->email])
            ->assertOk();

        Mail::assertSent(SendOtpMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_resend_otp_does_not_leak_or_mail_active_account(): void
    {
        Mail::fake();
        $active = User::factory()->create(['status' => 'active']);

        $this->postJson('/api/resend-otp', ['email' => $active->email])
            ->assertOk();

        Mail::assertNotSent(SendOtpMail::class);
    }

    public function test_resend_otp_rate_limited_per_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['status' => 'pending']);

        foreach (range(1, 3) as $i) {
            $this->postJson('/api/resend-otp', ['email' => $user->email])->assertOk();
        }

        $this->postJson('/api/resend-otp', ['email' => $user->email])->assertStatus(429);
    }
}
