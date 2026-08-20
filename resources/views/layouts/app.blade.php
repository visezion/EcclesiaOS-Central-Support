@php
    $user = auth()->user();
    $currentRoute = request()->route()?->getName();
    $isActive = fn (string $route): bool => $currentRoute === $route || str_starts_with((string) $currentRoute, $route.'.');
    $navSections = [
        'Overview' => [['Dashboard', 'dashboard', '⌂']],
        'Support Operations' => [
            ['Tickets', 'support.tickets', '▤'],
            ['Community', 'support.community', '◌'],
            ['Knowledge Base', 'support.knowledge', '▥'],
            ['Live Support', 'support.live', '◉'],
        ],
        'Knowledge Tools' => [['Categories', 'support.knowledge.categories', '+']],
        'Administration' => [
            ['Update Center', 'system.update.page', '↻'],
            ['Central Connection', 'support.connection', '⌁'],
            ['Audit Log', 'support.audit', '≡'],
        ],
    ];
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title')) ?: 'Dashboard' }} - EcclesiaOS Central Support</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <div class="app-shell min-h-screen">
        <aside class="app-sidebar">
            <div class="app-sidebar-scroll">
                <div class="app-brand">
                    <div class="app-brand-mark">✝</div>
                    <div class="min-w-0">
                        <div class="app-brand-name">EcclesiaOS</div>
                        <div class="app-brand-subtitle">Central Support</div>
                    </div>
                </div>

                @foreach ($navSections as $section => $items)
                    <div class="app-nav-section">{{ $section }}</div>
                    <nav class="space-y-1">
                        @foreach ($items as [$label, $route, $icon])
                            <a href="{{ route($route) }}" class="app-nav-item {{ $isActive($route) ? 'app-nav-active' : '' }}" aria-current="{{ $isActive($route) ? 'page' : 'false' }}">
                                <span class="app-nav-icon">{{ $icon }}</span>
                                <span>{{ $label }}</span>
                            </a>
                        @endforeach
                    </nav>
                @endforeach
            </div>

            <div class="app-profile">
                <div class="app-avatar">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium">{{ $user?->name }}</div>
                    <div class="truncate text-xs text-slate-300">Support administrator</div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-emerald-300"><span class="size-2 rounded-full bg-emerald-400"></span> Online</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="app-logout" title="Sign out" aria-label="Sign out">↪</button></form>
            </div>
        </aside>

        <div class="app-content">
            <header class="app-topbar">
                <div class="app-topbar-inner">
                    <div class="min-w-0">
                        <p class="truncate text-sm text-slate-600"><span class="font-medium text-slate-900">Welcome back, {{ str($user?->name ?? 'there')->explode(' ')->first() }}!</span> Here is what is happening today.</p>
                    </div>
                    <div class="ml-auto flex items-center gap-2">
                        <div class="app-date">◷ <span>{{ now()->format('M d, Y') }}</span></div>
                        <div class="app-topbar-avatar">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</div>
                    </div>
                </div>
            </header>
            <main class="app-main">@yield('content')</main>
            <footer class="app-footer" aria-label="Application version">
                <span>EcclesiaOS Central Support</span>
                <span>Version {{ config('app.version', 'development') }}</span>
            </footer>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
