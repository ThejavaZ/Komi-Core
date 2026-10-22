<?php

namespace App\Http\Controllers;

use App\Support\Xp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XpController extends Controller
{
    /**
     * Resumen de XP/nivel del usuario autenticado (para la tarjeta de perfil).
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => array_merge(
                Xp::progress((int) $user->xp),
                ['actions' => Xp::ACTIONS],
            ),
        ]);
    }
}
