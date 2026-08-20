<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class AuthController
{
    public function create(): View|Response|RedirectResponse
    {
        if (Schema::hasTable('users') && ! User::query()->where('is_super_admin', true)->exists()) {
            return redirect()->route('setup');
        }

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return response()->view('auth.login')->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache');
    }

    public function store(Request $request): RedirectResponse
    {
        if (Schema::hasTable('users') && ! User::query()->where('is_super_admin', true)->exists()) {
            return redirect()->route('setup');
        }

        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email', 'remember'))->withErrors(['email' => 'The email or password is incorrect.']);
        }
        if (! $request->user()?->is_super_admin) {
            Auth::logout();

            return back()->withInput($request->only('email', 'remember'))->withErrors(['email' => 'This account is not a Super Administrator.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
