<?php

namespace App\Http\Requests;

use App\Models\UserBlock;
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
                // Consulta directa al pivot: el scope global de User ocultaria
                // al usuario bloqueado y la validacion se saltaria el bloqueo.
                $hasConflict = UserBlock::query()
                    ->where(function ($query) use ($me, $targetId) {
                        $query->where('blocker_id', $me->id)
                            ->where('blocked_id', $targetId)
                            ->orWhere('blocker_id', $targetId)
                            ->where('blocked_id', $me->id);
                    })
                    ->exists();

                if ($hasConflict) {
                    $validator->errors()->add('user_id', 'No puedes iniciar un chat con este usuario.');
                }
            }
        });
    }
}
