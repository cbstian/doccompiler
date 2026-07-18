<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidApiClient
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $hashedToken = $token ? hash('sha256', $token) : null;

        $client = $hashedToken
            ? ApiClient::active()->where('token', $hashedToken)->first()
            : null;

        if (! $client) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Token inválido',
                ],
            ], 401);
        }

        $client->update(['last_used_at' => now()]);
        $request->attributes->set('apiClient', $client);

        return $next($request);
    }
}
