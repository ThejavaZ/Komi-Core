<?php

namespace App\Http\Requests;

use App\Models\StoreItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:2000',
            'type' => ['nullable', 'string', 'in:'.StoreItem::TYPE_TEMPLATE.','.StoreItem::TYPE_SUBSCRIPTION.','.StoreItem::TYPE_ITEM],
            'price' => 'required|integer|min:0|max:1000000',
            'preview_data' => 'nullable|array',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Errores de validación',
            'errors' => $validator->errors(),
        ], 422));
    }
}
