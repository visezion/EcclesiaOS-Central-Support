<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title')) ?: 'Dashboard' }} · EcclesiaOS Central Support</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="w-full bg-slate-950 px-5 py-6 text-white lg:min-h-screen lg:w-72 lg:shrink-0">
            <div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-2xl bg-violet-500/20 text-violet-300">✦</span><div><div class="font-black tracking-tight">EcclesiaOS</div><div class="text-xs text-slate-400">Central Support</div></div></div>
            @auth
                <nav class="mt-10 space-y-1 text-sm"><a href="{{ route('dashboard') }}" class="block rounded-xl px-4 py-3 font-semibold text-white hover:bg-white/10">Overview</a><a href="{{ route('support.tickets') }}" class="block rounded-xl px-4 py-3 text-slate-300 hover:bg-white/10">Tickets</a><a href="{{ route('support.community') }}" class="block rounded-xl px-4 py-3 text-slate-300 hover:bg-white/10">Community</a><a href="{{ route('support.knowledge') }}" class="block rounded-xl px-4 py-3 text-slate-300 hover:bg-white/10">Knowledge Base</a><a href="{{ route('support.live') }}" class="block rounded-xl px-4 py-3 text-slate-300 hover:bg-white/10">Live Support</a><a href="{{ route('support.connection') }}" class="block rounded-xl px-4 py-3 text-slate-300 hover:bg-white/10">Central Connection</a><a href="{{ route('support.audit') }}" class="block rounded-xl px-4 py-3 text-slate-300 hover:bg-white/10">Audit Log</a></nav>
                <div class="mt-auto pt-10 text-xs text-slate-400">Signed in as<br><span class="font-semibold text-slate-200">{{ auth()->user()->name }}</span></div>
                <form method="POST" action="{{ route('logout') }}" class="mt-5">@csrf<button class="w-full rounded-xl border border-white/10 px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/10">Sign out</button></form>
            @endauth
        </aside>
        <main class="min-w-0 flex-1 p-5 sm:p-8">@yield('content')</main>
    </div>
</body>
</html>
