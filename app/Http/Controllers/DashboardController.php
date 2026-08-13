<?php

namespace App\Http\Controllers;

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
        $data = $request->validate(['installation_id' => ['required', 'string', 'max:120'], 'church_name' => ['required', 'string', 'max:180'], 'callback_url' => ['nullable', 'url', 'max:500']]);
        $token = config('support.installation_token_prefix', 'eco_').Str::random(56);
        Installation::query()->updateOrCreate(
            ['installation_id' => $data['installation_id']],
            ['church_name' => $data['church_name'], 'callback_url' => $data['callback_url'] ?? null, 'token_hash' => hash('sha256', $token), 'token_encrypted' => Crypt::encryptString($token), 'enabled' => true],
        );

        return back()->with('installation_token', $token)->with('status', 'Installation connected. Copy the token now; it will not be shown again.');
    }

    public function toggleInstallation(Installation $installation): RedirectResponse
    {
        $installation->update(['enabled' => ! $installation->enabled]);

        return back()->with('status', $installation->enabled ? 'Installation enabled.' : 'Installation disabled.');
    }
}
