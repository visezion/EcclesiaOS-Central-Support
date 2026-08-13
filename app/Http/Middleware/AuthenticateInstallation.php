<?php

namespace App\Http\Middleware;

use App\Models\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateInstallation
{
    public function handle(Request $request, Closure $next): Response
    {
        $installationId = (string) $request->header('X-EcclesiaOS-Installation');
        $token = (string) $request->bearerToken();
        $installation = Installation::query()->where('installation_id', $installationId)->where('enabled', true)->first();

        abort_unless($installation && $token !== '' && hash_equals($installation->token_hash, hash('sha256', $token)), 401, 'Invalid installation credentials.');

        $installation->forceFill([
            'version' => $request->header('X-EcclesiaOS-Version', $installation->version),
            'last_seen_at' => now(),
        ])->save();
        $request->attributes->set('installation', $installation);

        return $next($request);
    }
}
