<?php

namespace Database\Seeders;

use App\Models\Community;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $communities = [
            ['name' => 'Tech & Code', 'slug' => 'tech-code', 'description' => 'Comunidad para desarrolladores y entusiastas de la tecnologia.'],
            ['name' => 'Gaming Zone', 'slug' => 'gaming-zone', 'description' => 'Noticias, reviews y discusiones sobre videojuegos.'],
            ['name' => 'Arte Digital', 'slug' => 'arte-digital', 'description' => 'Comparte tu arte digital, ilustraciones y disenos.'],
            ['name' => 'Ciencia y Descubrimientos', 'slug' => 'ciencia', 'description' => 'Noticias cientificas y descubrimientos recientes.'],
            ['name' => 'Anime & Manga', 'slug' => 'anime-manga', 'description' => 'Todo sobre anime, manga y cultura japonesa.'],
            ['name' => 'Fitness & Salud', 'slug' => 'fitness-salud', 'description' => 'Rutinas, nutricion y bienestar general.'],
            ['name' => 'Fotografia', 'slug' => 'fotografia', 'description' => 'Comparte tus fotos y aprende tecnicas de fotografia.'],
            ['name' => 'Musica & Produccion', 'slug' => 'musica', 'description' => 'Produccion musical, instrumentos y descubrimientos sonoros.'],
            ['name' => 'Emprendimiento', 'slug' => 'emprendimiento', 'description' => 'Ideas, startups y consejos para emprendedores.'],
            ['name' => 'Off-Topic', 'slug' => 'off-topic', 'description' => 'Habla de lo que quieras, aqui no hay reglas.'],
        ];

        foreach ($communities as $data) {
            Community::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
