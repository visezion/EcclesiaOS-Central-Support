@extends('layouts.app')

@section('title', 'Update Center')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-violet-600">System maintenance</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Update Center</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Pull the latest release from GitHub, rebuild the Docker application, run migrations, and refresh the production cache.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm font-bold text-violet-600 hover:text-violet-700">Back to overview</a>
    </div>

    @if(session('status'))<div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>@endif

    <section class="mt-8 rounded-3xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">GitHub release</p>
                <h2 class="mt-2 text-2xl font-black text-indigo-950">Update the central support server</h2>
                <p id="update-message" class="mt-2 max-w-xl text-sm leading-6 text-indigo-800">{{ $update_status['message'] ?? 'Ready to check GitHub for updates.' }}</p>
            </div>
            <form method="POST" action="{{ route('system.update') }}" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').textContent='Starting update…';">
                @csrf
                <button class="w-full rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60 sm:w-auto">Update now</button>
            </form>
        </div>
        <div id="update-meta" class="mt-6 border-t border-indigo-200 pt-4 text-xs font-semibold text-indigo-700">Status: {{ ucfirst($update_status['state'] ?? 'idle') }}{{ isset($update_status['commit']) ? ' · '.$update_status['commit'] : '' }}</div>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-black text-slate-950">What happens during an update?</h2>
        <ol class="mt-4 grid gap-4 text-sm text-slate-600 sm:grid-cols-4">
            <li><span class="font-black text-violet-600">01</span><p class="mt-1">Pull the selected GitHub branch.</p></li>
            <li><span class="font-black text-violet-600">02</span><p class="mt-1">Build and restart changed containers.</p></li>
            <li><span class="font-black text-violet-600">03</span><p class="mt-1">Apply pending database migrations.</p></li>
            <li><span class="font-black text-violet-600">04</span><p class="mt-1">Refresh Laravel caches and report the result.</p></li>
        </ol>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const message = document.getElementById('update-message');
    const meta = document.getElementById('update-meta');
    if (!message || !meta) return;
    const poll = async () => {
        try {
            const response = await fetch('{{ route('system.update.status') }}', {headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            const status = await response.json();
            message.textContent = status.message || 'Checking update status…';
            meta.textContent = 'Status: ' + (status.state || 'idle').replace(/^./, c => c.toUpperCase()) + (status.commit ? ' · ' + status.commit : '');
            if (status.state === 'queued' || status.state === 'running') window.setTimeout(poll, 3000);
        } catch (_) { window.setTimeout(poll, 5000); }
    };
    poll();
})();
</script>
@endpush
