<?php

namespace App\Services;

use App\Models\AutoModKeyword;
use App\Models\Report;
use Illuminate\Support\Facades\Cache;

class AutoModService
{
    /**
     * Acciones posibles: flag (reporte automático), block (rechaza), delete (rechaza y marca).
     * Se cachean las keywords para no golpear la BD en cada post/comentario.
     *
     * @return array{action: string, keyword: string}|null
     */
    public function check(string $content): ?array
    {
        $keywords = Cache::remember('automod.keywords', 300, function () {
            return AutoModKeyword::all(['keyword', 'action']);
        });

        $lower = mb_strtolower($content);

        foreach ($keywords as $kw) {
            if (str_contains($lower, mb_strtolower($kw->keyword))) {
                return ['action' => $kw->action, 'keyword' => $kw->keyword];
            }
        }

        return null;
    }

    /**
     * Crea un reporte automático (flag) atribuible al sistema (user_id null).
     */
    public function flag(string $reportableType, int $reportableId, string $keyword, ?int $userId = null): void
    {
        Report::create([
            'user_id' => $userId,
            'reportable_type' => $reportableType,
            'reportable_id' => $reportableId,
            'reason' => 'automod',
            'description' => "Detectado por automod: «{$keyword}»",
            'status' => 'pending',
        ]);
    }
}
