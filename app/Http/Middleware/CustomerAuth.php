<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CustomerAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $pat = PersonalAccessToken::findToken($token);

        if (! $pat || ! $pat->tokenable instanceof \App\Models\Customer) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $pat->forceFill(['last_used_at' => now()])->save();

        $request->setUserResolver(fn () => $pat->tokenable);
        auth()->shouldUse('customers');

        return $next($request);
    }
}
