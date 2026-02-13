<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            abort(403, 'No autorizado');
        }

        return $next($request);
    }
}
