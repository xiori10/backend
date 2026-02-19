<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserSession;

class UpdateLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->user() && $request->user()->currentAccessToken()) {
            $tokenId = $request->user()->currentAccessToken()->id;

            UserSession::where('token_id', $tokenId)
                ->update(['last_activity' => now()]);
        }

        return $response;
    }
}
