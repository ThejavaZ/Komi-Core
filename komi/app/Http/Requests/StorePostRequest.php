<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'in:text,image,poll,quiz,wiki,question,link,list'],
            'community_id' => ['nullable', 'integer', 'exists:communities,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'poll_options' => ['nullable', 'array', 'min:2', 'max:6'],
            'poll_options.*' => ['required', 'string', 'max:200'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['required', 'string', 'max:30'],
            // Quiz
            'quiz_title' => ['nullable', 'string', 'max:255'],
            'quiz_description' => ['nullable', 'string', 'max:1000'],
            'quiz_questions' => ['nullable', 'array', 'min:1', 'max:20'],
            'quiz_questions.*.question' => ['required_with:quiz_questions', 'string', 'max:500'],
            'quiz_questions.*.answers' => ['required_with:quiz_questions', 'array', 'min:2', 'max:6'],
            'quiz_questions.*.answers.*.text' => ['required_with:quiz_questions', 'string', 'max:200'],
            'quiz_questions.*.answers.*.is_correct' => ['required_with:quiz_questions', 'boolean'],
            'quiz_questions.*.explanation' => ['nullable', 'string', 'max:500'],
            // Wiki
            'wiki_title' => ['nullable', 'string', 'max:255'],
            // Question
            'is_solved' => ['nullable', 'boolean'],
        ];
    }
}
