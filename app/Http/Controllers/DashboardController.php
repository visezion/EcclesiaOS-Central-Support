<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CommunityQuestion;
use App\Models\Installation;
use App\Models\SupportEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class DashboardController
{
    public function index(): View
    {
        return view('dashboard', [
            'installations' => Installation::query()->latest('last_seen_at')->get(),
            'events' => SupportEvent::query()->latest()->limit(10)->get(),
            'questions' => CommunityQuestion::query()->latest()->limit(8)->get(),
            'stats' => [
                'installations' => Installation::query()->where('enabled', true)->count(),
                'active_today' => Installation::query()->where('last_seen_at', '>=', now()->subDay())->count(),
                'events' => SupportEvent::query()->count(),
                'questions' => CommunityQuestion::query()->where('status', 'pending_review')->count(),
            ],
        ]);
    }

    public function createToken(Request $request): RedirectResponse
    {
        $data = $request->validate(['installation_id' => ['required', 'string', 'max:120'], 'church_name' => ['required', 'string', 'max:180'], 'callback_url' => ['nullable', 'url', 'max:500', function (string $attribute, mixed $value, \Closure $fail): void {
            if (blank($value)) {
                return;
            }
            $parts = parse_url((string) $value);
            if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])) {
                $fail('The callback URL must be a public URL without credentials, query parameters, or fragments.');
            }
            if (app()->environment('production') && strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
                $fail('Production callback URLs must use HTTPS.');
            }
        }]]);
        $token = config('support.installation_token_prefix', 'eco_').Str::random(56);
        Installation::query()->updateOrCreate(
            ['installation_id' => $data['installation_id']],
            ['church_name' => $data['church_name'], 'callback_url' => $data['callback_url'] ?? null, 'token_hash' => hash('sha256', $token), 'token_encrypted' => Crypt::encryptString($token), 'enabled' => true],
        );
        AuditLog::query()->create(['user_id' => auth()->id(), 'action' => 'installation.token_rotated', 'installation_id' => $data['installation_id'], 'metadata' => ['church_name' => $data['church_name']], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('installation_token', $token)->with('status', 'Installation connected. Copy the token now; it will not be shown again.');
    }

    public function toggleInstallation(Installation $installation): RedirectResponse
    {
        $installation->update(['enabled' => ! $installation->enabled]);
        AuditLog::query()->create(['user_id' => auth()->id(), 'action' => $installation->enabled ? 'installation.enabled' : 'installation.disabled', 'installation_id' => $installation->installation_id, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);

        return back()->with('status', $installation->enabled ? 'Installation enabled.' : 'Installation disabled.');
    }
}
