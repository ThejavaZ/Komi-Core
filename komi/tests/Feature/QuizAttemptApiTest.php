<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizAttemptApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{quiz: Quiz, q1: int, q2: int, correct1: int, correct2: int, wrong1: int, wrong2: int}
     */
    private function createQuizPost(User $author, array $quizOptions = []): array
    {
        $post = $author->posts()->create([
            'content' => 'Responde el quiz',
            'type' => 'quiz',
        ]);

        $quiz = $post->quiz()->create(array_merge([
            'title' => 'Quiz de prueba',
        ], $quizOptions));

        $q1 = $quiz->questions()->create(['question' => 'Pregunta 1', 'order' => 0, 'explanation' => 'Explicacion 1']);
        $q2 = $quiz->questions()->create(['question' => 'Pregunta 2', 'order' => 1]);

        $correct1 = $q1->answers()->create(['answer_text' => 'Correcta 1', 'is_correct' => true, 'order' => 0]);
        $wrong1 = $q1->answers()->create(['answer_text' => 'Mala 1', 'is_correct' => false, 'order' => 1]);
        $correct2 = $q2->answers()->create(['answer_text' => 'Correcta 2', 'is_correct' => true, 'order' => 0]);
        $wrong2 = $q2->answers()->create(['answer_text' => 'Mala 2', 'is_correct' => false, 'order' => 1]);

        return [
            'quiz' => $quiz,
            'q1' => $q1->id,
            'q2' => $q2->id,
            'correct1' => $correct1->id,
            'correct2' => $correct2->id,
            'wrong1' => $wrong1->id,
            'wrong2' => $wrong2->id,
        ];
    }

    public function test_user_can_submit_attempt_and_get_score(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'correct1' => $correct1, 'correct2' => $correct2, 'wrong2' => $wrong2] =
            $this->createQuizPost($author, ['show_results_immediately' => true]);

        $me = User::factory()->create();
        Sanctum::actingAs($me);

        $response = $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $correct1],
                ['question_id' => $q2, 'answer_id' => $wrong2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.score', 1)
            ->assertJsonPath('data.total_questions', 2)
            ->assertJsonPath('data.questions.0.is_correct', true)
            ->assertJsonPath('data.questions.1.is_correct', false)
            ->assertJsonPath('data.questions.1.correct_answer_id', $correct2);

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $me->id,
            'score' => 1,
            'total_questions' => 2,
        ]);
    }

    public function test_attempt_requires_answers_for_every_question(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'correct1' => $correct1] = $this->createQuizPost($author);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $correct1],
            ],
        ])->assertStatus(422);
    }

    public function test_attempt_rejects_answer_that_does_not_belong_to_question(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'wrong2' => $wrong2] =
            $this->createQuizPost($author);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $wrong2],
                ['question_id' => $q2, 'answer_id' => $wrong2],
            ],
        ])->assertStatus(422);
    }

    public function test_quiz_without_retake_rejects_second_attempt(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'correct1' => $c1, 'correct2' => $c2] =
            $this->createQuizPost($author, ['allow_retake' => false]);

        $me = User::factory()->create();
        Sanctum::actingAs($me);

        $answers = [
            ['question_id' => $q1, 'answer_id' => $c1],
            ['question_id' => $q2, 'answer_id' => $c2],
        ];

        $this->postJson("/api/quizzes/{$quiz->id}/attempt", ['answers' => $answers])
            ->assertCreated()
            ->assertJsonPath('data.score', 2);

        $this->postJson("/api/quizzes/{$quiz->id}/attempt", ['answers' => $answers])
            ->assertStatus(422);
    }

    public function test_retake_updates_existing_attempt_instead_of_duplicating(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'correct1' => $c1, 'correct2' => $c2, 'wrong1' => $wrong1, 'wrong2' => $wrong2] =
            $this->createQuizPost($author, ['allow_retake' => true]);

        $me = User::factory()->create();
        Sanctum::actingAs($me);

        $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $wrong1],
                ['question_id' => $q2, 'answer_id' => $wrong2],
            ],
        ])->assertCreated()->assertJsonPath('data.score', 0);

        $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $c1],
                ['question_id' => $q2, 'answer_id' => $c2],
            ],
        ])->assertCreated()->assertJsonPath('data.score', 2);

        $this->assertSame(1, $quiz->attempts()->where('user_id', $me->id)->count());
        $this->assertSame(2, $quiz->attempts()->where('user_id', $me->id)->first()->answers()->count());
    }

    public function test_results_are_hidden_when_show_results_immediately_is_false(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'correct1' => $c1, 'correct2' => $c2] =
            $this->createQuizPost($author, ['show_results_immediately' => false]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $c1],
                ['question_id' => $q2, 'answer_id' => $c2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.score', 2)
            ->assertJsonMissingPath('data.questions');
    }

    public function test_leaderboard_orders_by_score(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'correct1' => $c1, 'correct2' => $c2, 'wrong1' => $wrong1, 'wrong2' => $wrong2] =
            $this->createQuizPost($author);

        $low = User::factory()->create();
        $high = User::factory()->create();

        Sanctum::actingAs($low);
        $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $wrong1],
                ['question_id' => $q2, 'answer_id' => $wrong2],
            ],
        ])->assertCreated();

        Sanctum::actingAs($high);
        $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $c1],
                ['question_id' => $q2, 'answer_id' => $c2],
            ],
        ])->assertCreated();

        $response = $this->getJson("/api/quizzes/{$quiz->id}/leaderboard");

        $response->assertOk()
            ->assertJsonPath('data.0.position', 1)
            ->assertJsonPath('data.0.score', 2)
            ->assertJsonPath('data.0.user.id', $high->id)
            ->assertJsonPath('data.1.position', 2)
            ->assertJsonPath('data.1.score', 0)
            ->assertJsonPath('data.1.user.id', $low->id);
    }

    public function test_my_attempt_endpoint_returns_current_attempt(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'correct1' => $c1, 'correct2' => $c2] = $this->createQuizPost($author);

        $me = User::factory()->create();
        Sanctum::actingAs($me);

        $this->getJson("/api/quizzes/{$quiz->id}/me")
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $c1],
                ['question_id' => $q2, 'answer_id' => $c2],
            ],
        ])->assertCreated();

        $this->getJson("/api/quizzes/{$quiz->id}/me")
            ->assertOk()
            ->assertJsonPath('data.total_questions', 2);
    }

    public function test_post_resource_includes_quiz_my_attempt(): void
    {
        $author = User::factory()->create();
        ['quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'correct1' => $c1, 'correct2' => $c2] =
            $this->createQuizPost($author);

        $me = User::factory()->create();
        Sanctum::actingAs($me);

        $this->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                ['question_id' => $q1, 'answer_id' => $c1],
                ['question_id' => $q2, 'answer_id' => $c2],
            ],
        ])->assertCreated();

        $this->getJson("/api/posts/{$quiz->post_id}")
            ->assertOk()
            ->assertJsonPath('data.quiz.my_attempt.score', 2)
            ->assertJsonPath('data.quiz.my_attempt.total_questions', 2)
            ->assertJsonPath('data.quiz.title', 'Quiz de prueba');
    }
}
