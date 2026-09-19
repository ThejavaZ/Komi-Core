<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSessionController extends Controller
{
    public function index(): JsonResponse
    {
        $sessions = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orWhere('payload', 'like', '%"is_admin":true%')
            ->latest('last_activity')
            ->get()
            ->map(function ($session) {
                $payload = unserialize($session->payload);
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $this->parseUserAgent($session->user_agent),
                    'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                    'is_current' => $session->id === request()->session()->getId(),
                ];
            });

        return response()->json(['sessions' => $sessions]);
    }

    public function destroy($sessionId): JsonResponse
    {
        if ($sessionId === request()->session()->getId()) {
            return response()->json(['message' => 'No puedes cerrar tu propia sesión desde aquí.'], 400);
        }

        DB::table('sessions')->where('id', $sessionId)->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    private function parseUserAgent($ua): array
    {
        $browser = 'Desconocido';
        $os = 'Desconocido';

        if (str_contains($ua, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($ua, 'Edg')) $browser = 'Edge';
        elseif (str_contains($ua, 'Chrome')) $browser = 'Chrome';
        elseif (str_contains($ua, 'Safari')) $browser = 'Safari';

        if (str_contains($ua, 'Windows')) $os = 'Windows';
        elseif (str_contains($ua, 'Mac OS')) $os = 'macOS';
        elseif (str_contains($ua, 'Linux')) $os = 'Linux';
        elseif (str_contains($ua, 'Android')) $os = 'Android';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';

        return ['browser' => $browser, 'os' => $os];
    }
}
