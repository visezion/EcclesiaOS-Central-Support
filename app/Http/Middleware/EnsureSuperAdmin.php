<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('users') || ! User::query()->where('is_super_admin', true)->exists()) {
            return redirect()->route('setup');
        }

        if (! $request->user()?->is_super_admin) {
            abort(403, 'Super Administrator access is required.');
        }

        return $next($request);
    }
}
