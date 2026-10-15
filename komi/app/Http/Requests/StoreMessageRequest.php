<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
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
            // El texto es opcional si se adjunta medio (imagen/gif/sticker):
            // se exige al menos uno de texto, imagen o sticker.
            'content' => ['required_without_all:image,sticker_meta', 'nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', Rule::in(['text', 'image', 'gif', 'sticker'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'sticker_meta' => ['nullable', 'array'],
            'sticker_meta.pack' => ['nullable', 'string', 'max:100'],
            'sticker_meta.emoji' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'content.required_without_all' => 'El mensaje necesita texto o un medio adjunto.',
            'content.max' => 'El mensaje no puede superar los 5000 caracteres.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.mimes' => 'Formatos permitidos: jpg, jpeg, png, webp o gif.',
            'image.max' => 'La imagen no puede superar los 8 MB.',
        ];
    }
}
