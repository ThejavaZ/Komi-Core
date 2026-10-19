<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizAttemptController extends Controller
{
    public function attempt(Request $request, Quiz $quiz): JsonResponse
    {
        $quiz->load('questions.answers');
        $total = $quiz->questions->count();

        if ($total === 0) {
            abort(422, 'Este quiz no tiene preguntas.');
        }

        $data = $request->validate([
            'answers' => ['required', 'array', 'size:'.$total],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer_id' => ['required', 'integer'],
            'time_taken_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $expected = $quiz->questions->pluck('id')->sort()->values()->all();
        $given = collect($data['answers'])->pluck('question_id')->sort()->values()->all();

        if ($expected !== $given) {
            abort(422, 'Debes responder exactamente todas las preguntas del quiz.');
        }

        $score = 0;
        $breakdown = [];

        foreach ($data['answers'] as $answer) {
            $question = $quiz->questions->firstWhere('id', $answer['question_id']);
            $chosen = $question->answers->firstWhere('id', $answer['answer_id']);

            if (! $chosen) {
                abort(422, 'Una de las respuestas no pertenece a su pregunta.');
            }

            $isCorrect = (bool) $chosen->is_correct;
            if ($isCorrect) {
                $score++;
            }

            $correctAnswer = $question->answers->firstWhere('is_correct', true);

            $breakdown[] = [
                'question_id' => $question->id,
                'answer_id' => $chosen->id,
                'is_correct' => $isCorrect,
                'correct_answer_id' => $correctAnswer?->id,
                'explanation' => $question->explanation,
            ];
        }

        $user = $request->user();
        $attempt = $quiz->attempts()->where('user_id', $user->id)->first();

        if ($attempt && ! $quiz->allow_retake) {
            abort(422, 'Este quiz no permite reintentos. Ya tienes un intento registrado.');
        }

        $attributes = [
            'user_id' => $user->id,
            'score' => $score,
            'total_questions' => $total,
            'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
        ];

        if ($attempt) {
            $attempt->answers()->delete();
            $attempt->update($attributes);
        } else {
            $attempt = $quiz->attempts()->create($attributes);
        }

        foreach ($breakdown as $row) {
            $attempt->answers()->create([
                'quiz_question_id' => $row['question_id'],
                'quiz_answer_id' => $row['answer_id'],
                'is_correct' => $row['is_correct'],
            ]);
        }

        $payload = [
            'attempt_id' => $attempt->id,
            'score' => $score,
            'total_questions' => $total,
            'allow_retake' => (bool) $quiz->allow_retake,
        ];

        if ($quiz->show_results_immediately) {
            $payload['questions'] = $breakdown;
        }

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ], 201);
    }

    public function leaderboard(Request $request, Quiz $quiz): JsonResponse
    {
        $attempts = $quiz->attempts()
            ->with('user:id,name,username,avatar')
            ->orderByDesc('score')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $attempts->map(fn ($attempt, $index) => [
                'position' => $index + 1,
                'score' => (int) $attempt->score,
                'total_questions' => (int) $attempt->total_questions,
                'created_at' => $attempt->created_at?->toISOString(),
                'user' => $attempt->user ? [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'username' => $attempt->user->username,
                    'avatar_url' => $attempt->user->avatar,
                ] : null,
            ])->values(),
        ]);
    }

    public function myAttempt(Request $request, Quiz $quiz): JsonResponse
    {
        $attempt = $quiz->attempts()
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $attempt ? [
                'attempt_id' => $attempt->id,
                'score' => (int) $attempt->score,
                'total_questions' => (int) $attempt->total_questions,
                'created_at' => $attempt->created_at?->toISOString(),
            ] : null,
        ]);
    }
}
