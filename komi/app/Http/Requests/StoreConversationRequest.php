<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $me = $this->user();
            $targetId = (int) $this->input('user_id');

            if ($me && $targetId === $me->id) {
                $validator->errors()->add('user_id', 'No puedes iniciar un chat contigo mismo.');
            }

            if ($me && $targetId !== $me->id) {
                $target = User::find($targetId);

                if ($target && ($me->isBlocking($target) || $target->isBlocking($me))) {
                    $validator->errors()->add('user_id', 'No puedes iniciar un chat con este usuario.');
                }
            }
        });
    }
}
