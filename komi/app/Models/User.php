<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes; // 🔑 Agregado para soportar deleted_at
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 🔒 Necesario para emitir tokens en Flutter

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'birth_date',
    'bio',
    'avatar',
    'banner',
    'theme_color',
    'gender',
    'status',
    'is_verified',
    'is_premium',
    'is_global_admin'
])]
#[Hidden([
    'password',
    'remember_token'
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes; // ⚡ HasApiTokens y SoftDeletes añadidos aquí

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date', // Cast automático a tipo fecha de PHP
            'is_verified' => 'boolean', // Convierte el 1/0 de la DB a true/false de PHP
            'is_premium' => 'boolean',
            'is_global_admin' => 'boolean',
        ];
    }
}
