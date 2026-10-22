<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientLogRequest;
use App\Http\Requests\FeatureEventRequest;
use App\Models\ClientLog;
use App\Models\FeatureEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    /**
     * Registra el uso de una feature del cliente (post creado, chat abierto…).
     * Upsert por nombre: solo incrementa el contador y refresca last_seen_at.
     */
    public function feature(FeatureEventRequest $request): JsonResponse
    {
        $feature = $request->validated('feature');

        FeatureEvent::upsert(
            [
                'feature' => $feature,
                'occurrences_count' => 1,
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            ['feature'],
            ['occurrences_count' => DB::raw('occurrences_count + 1'), 'last_seen_at' => now(), 'updated_at' => now()]
        );

        return response()->json(['status' => 'success'], 201);
    }
}