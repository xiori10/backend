<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Si no hay usuario autenticado
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Si el usuario no es admin
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Si pasa ambas validaciones, continúa con la petición
        return $next($request);
    }
}
