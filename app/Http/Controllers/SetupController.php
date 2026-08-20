<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class SetupController
{
    public function create(): View|Response|RedirectResponse
    {
        if (Schema::hasTable('users') && User::query()->where('is_super_admin', true)->exists()) {
            return redirect()->route('login');
        }

        return response()->view('setup')->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if (! Schema::hasTable('users')) {
            return back()->withErrors(['setup' => 'Run the database migrations before creating the first account.']);
        }

        if (User::query()->where('is_super_admin', true)->exists()) {
            return redirect()->route('login')->withErrors(['setup' => 'Initial setup has already been completed.']);
        }

        $user = DB::transaction(fn (): User => User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => now(),
            'password' => $data['password'],
            'is_super_admin' => true,
        ]));

        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Super Administrator account created successfully.');
    }
}
