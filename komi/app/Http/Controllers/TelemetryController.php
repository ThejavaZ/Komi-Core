<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientLogRequest;
use App\Models\ClientLog;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    /**
     * Registra un error reportado desde el cliente (Flutter).
     *
     * Deduplicación por `error_hash` (md5 de message + stack_trace):
     * - Si el hash ya existe → incrementa `occurrences_count` y actualiza
     *   `last_seen_at` (sin crear fila nueva).
     * - Si no existe → crea el registro con `occurrences_count = 1`.
     *
     * El endpoint es público (con throttle) para poder reportar fallos incluso
     * cuando la autenticación/Sanctum está rota del lado del cliente.
     */
    public function store(ClientLogRequest $request): JsonResponse
    {
        $message = $request->validated('message');
        $stackTrace = $request->validated('stack_trace');
        $appVersion = $request->validated('app_version');

        $errorHash = ClientLog::hashFor($message, $stackTrace);

        $log = ClientLog::firstOrCreate(
            ['error_hash' => $errorHash],
            [
                'message' => $message,
                'stack_trace' => $stackTrace,
                'app_version' => $appVersion,
                'occurrences_count' => 1,
                'last_seen_at' => now(),
            ]
        );

        // Ya existía → deduplicación: solo sumamos la ocurrencia y refrescamos
        // la fecha de la última aparición, sin crear una fila nueva.
        if (! $log->wasRecentlyCreated) {
            $log->increment('occurrences_count');
            $log->update(['last_seen_at' => now()]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Log registrado correctamente.',
        ], 201);
    }
}