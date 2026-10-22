<?php

namespace App\Support;

use App\Models\User;
use Throwable;

/**
 * Sistema de XP y niveles (global, por usuario).
 *
 * El XP se otorga SOLO desde el servidor en los puntos clave de la app;
 * el cliente nunca envía XP. La fórmula de nivel es determinista
 * (umbral del nivel L = 50·(L−1)²) para que el cliente pueda mostrar la
 * barra de progreso sin llamadas extra cuando solo tiene el total.
 */
class Xp
{
    /** XP otorgado por acción. Claves expuestas en /api/xp/summary. */
    public const ACTIONS = [
        'post.created' => 10,
        'comment.created' => 5,
        'like.received' => 2,
        'community.created' => 20,
        'checkin.claimed' => 15,
    ];

    public const MAX_LEVEL = 20;

    /** Nombre de cada nivel (índice 1..20). */
    public const LEVEL_NAMES = [
        1 => 'Novato',
        2 => 'Aprendiz',
        3 => 'Explorador',
        4 => 'Colaborador',
        5 => 'Habitual',
        6 => 'Veterano',
        7 => 'Experto',
        8 => 'Cronista',
        9 => 'Guardián',
        10 => 'Maestro',
        11 => 'Arquitecto',
        12 => 'Estratega',
        13 => 'Inspirador',
        14 => 'Referente',
        15 => 'Élite',
        16 => 'Leyenda',
        17 => 'Icono',
        18 => 'Mito',
        19 => 'Astral',
        20 => 'Supremo',
    ];

    /**
     * Otorga XP por la acción dada. Devuelve el XP concedido (0 si la
     * acción es desconocida). Nunca lanza: el XP no debe romper el flujo.
     */
    public static function award(User $user, string $action): int
    {
        $amount = self::ACTIONS[$action] ?? 0;
        if ($amount <= 0) {
            return 0;
        }

        try {
            $user->increment('xp', $amount);

            return $amount;
        } catch (Throwable) {
            return 0;
        }
    }

    /** XP acumulado mínimo para alcanzar el nivel dado. */
    public static function thresholdFor(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        return 50 * ($level - 1) ** 2;
    }

    /** Nivel resultante para un total de XP dado (sin bajar de 1 ni subir de 20). */
    public static function levelFor(int $xp): int
    {
        if ($xp <= 0) {
            return 1;
        }

        return min(self::MAX_LEVEL, (int) floor(sqrt($xp / 50)) + 1);
    }

    public static function levelName(int $level): string
    {
        return self::LEVEL_NAMES[min(max($level, 1), self::MAX_LEVEL)];
    }

    /**
     * Resumen de progreso para la UI (perfil / barra de nivel).
     *
     * @return array{level:int, level_name:string, xp_total:int, xp_into_level:int, xp_for_next:int|null, percent:int, next_level:int|null}
     */
    public static function progress(int $xp): array
    {
        $level = self::levelFor($xp);

        if ($level >= self::MAX_LEVEL) {
            return [
                'level' => $level,
                'level_name' => self::levelName($level),
                'xp_total' => $xp,
                'xp_into_level' => 0,
                'xp_for_next' => null,
                'percent' => 100,
                'next_level' => null,
            ];
        }

        $currentThreshold = self::thresholdFor($level);
        $nextThreshold = self::thresholdFor($level + 1);
        $forNext = $nextThreshold - $currentThreshold;
        $into = $xp - $currentThreshold;

        return [
            'level' => $level,
            'level_name' => self::levelName($level),
            'xp_total' => $xp,
            'xp_into_level' => $into,
            'xp_for_next' => $forNext,
            'percent' => $forNext > 0
                ? (int) min(100, floor($into * 100 / $forNext))
                : 100,
            'next_level' => $level + 1,
        ];
    }
}
