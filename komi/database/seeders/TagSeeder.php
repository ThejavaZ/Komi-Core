<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'tecnologia', 'programacion', 'flutter', 'laravel', 'ia',
            'gaming', 'anime', 'musica', 'arte', 'ciencia',
            'deportes', 'filosofia', 'humor', 'noticias', 'tutoriales',
            'opensource', 'linux', 'python', 'javascript', 'diseño',
            'fotografia', 'cinema', 'libros', 'recetas', 'viajes',
        ];

        foreach ($tags as $name) {
            Tag::firstOrCreate(
                ['name' => $name],
                ['slug' => \Illuminate\Support\Str::slug($name)]
            );
        }
    }
}
