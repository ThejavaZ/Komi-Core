<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Audit log global: quién hizo qué y cuándo (solo is_global_admin).
    public function logs(Request $request): JsonResponse
    {
        $query = AdminLog::with('admin');

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        $logs = $query->latest()->paginate(20);

        return response()->json($logs);
    }
}
