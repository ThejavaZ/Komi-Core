<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsGlobalAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->is_global_admin) {
            return response()->json(['message' => 'No tienes permisos de administrador.'], 403);
        }

        return $next($request);
    }
}
