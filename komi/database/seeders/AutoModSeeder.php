<?php

namespace Database\Seeders;

use App\Models\AutoModKeyword;
use Illuminate\Database\Seeder;

class AutoModSeeder extends Seeder
{
    /**
     * Reglas base de automod. `action`:
     * - flag: publica pero crea reporte automático para revisión de staff
     * - block: rechaza la publicación/comentario (422)
     * - delete: rechaza (reservado para contenido extremo; mismo efecto que block hoy)
     *
     * El staff puede añadir/editar reglas desde el admin (AdminAutoModController).
     */
    public function run(): void
    {
        $rules = [
            // Contenido que debe ir a revisión de staff, no se bloquea.
            ['keyword' => 'compra seguidores', 'action' => 'flag'],
            ['keyword' => 'comprar seguidores', 'action' => 'flag'],
            ['keyword' => 'giveaway falso', 'action' => 'flag'],
            ['keyword' => 'estafa', 'action' => 'flag'],
            ['keyword' => 'scam', 'action' => 'flag'],

            // Enlaces de descarga/warez y phishing.
            ['keyword' => 'cracked download', 'action' => 'block'],
            ['keyword' => 'keygen', 'action' => 'block'],
            ['keyword' => 'torrent gratis', 'action' => 'block'],

            // Amenazas y acoso extremo.
            ['keyword' => 'te voy a matar', 'action' => 'block'],
            ['keyword' => 'mataré', 'action' => 'block'],

            // Contacto sexual con menores — cero tolerancia.
            ['keyword' => 'menor de edad para', 'action' => 'delete'],
            ['keyword' => 'nena de', 'action' => 'delete'],
        ];

        foreach ($rules as $rule) {
            AutoModKeyword::firstOrCreate(
                ['keyword' => $rule['keyword']],
                ['action' => $rule['action']]
            );
        }
    }
}
