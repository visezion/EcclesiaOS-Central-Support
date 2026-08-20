<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Initial Setup · EcclesiaOS Central Support</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900">
<main class="grid min-h-screen lg:grid-cols-[.85fr_1.15fr]">
    <section class="hidden bg-gradient-to-br from-violet-700 via-indigo-800 to-slate-950 p-10 text-white lg:flex lg:flex-col lg:justify-between lg:p-16">
        <div><div class="text-sm font-semibold uppercase tracking-[.2em] text-violet-200">EcclesiaOS</div><h1 class="mt-8 max-w-lg text-3xl font-semibold leading-tight">Set up your Central Support workspace.</h1><p class="mt-5 max-w-md text-base leading-7 text-white/70">Create the first Super Administrator account to secure this installation and begin managing connected churches.</p></div>
        <p class="text-sm text-white/50">One-time installation setup · secure operations for connected churches</p>
    </section>
    <section class="flex items-center justify-center bg-slate-50 p-6 sm:p-10"><div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-7 shadow-2xl shadow-slate-950/10 sm:p-10"><div class="lg:hidden"><div class="text-sm font-semibold uppercase tracking-[.2em] text-violet-600">EcclesiaOS</div><div class="mt-1 text-slate-500">Central Support setup</div></div><div class="h-1.5 w-14 rounded-full bg-gradient-to-r from-violet-600 to-indigo-500"></div><h2 class="mt-6 text-2xl font-semibold">Create Super Administrator</h2><p class="mt-2 text-sm leading-6 text-slate-500">This page is available only until the first Super Administrator account is created.</p>
        @if($errors->any())<div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('setup.store') }}" class="mt-7 space-y-5">@csrf<label class="block"><span class="text-sm font-medium">Full name</span><input name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none"></label><label class="block"><span class="text-sm font-medium">Email address</span><input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none"></label><label class="block"><span class="text-sm font-medium">Password</span><input name="password" type="password" required minlength="12" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none"><span class="mt-1 block text-xs text-slate-500">Use at least 12 characters.</span></label><label class="block"><span class="text-sm font-medium">Confirm password</span><input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none"></label><button class="w-full rounded-xl bg-violet-600 px-4 py-3.5 text-sm font-semibold text-white hover:bg-violet-700">Create account and continue</button></form>
    </div></section>
</main>
</body>
</html>
