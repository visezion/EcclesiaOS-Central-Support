@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
<div class="mx-auto max-w-7xl">
    <h1 class="text-3xl font-black">Audit Log</h1>
    <p class="mt-2 text-sm text-slate-500">A tamper-evident operational history of support actions, connection changes, and moderation.</p>
    <div class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400"><tr><th class="p-5">Time</th><th class="p-5">Action</th><th class="p-5">Agent</th><th class="p-5">Installation</th><th class="p-5">Details</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($logs as $log)<tr><td class="whitespace-nowrap p-5 text-slate-500">{{ $log->created_at->format('Y-m-d H:i') }}</td><td class="p-5 font-semibold">{{ str($log->action)->replace('.', ' · ')->headline() }}</td><td class="p-5 text-slate-500">{{ $log->user_id ?: 'System/API' }}</td><td class="p-5 text-slate-500">{{ $log->installation_id ?: '—' }}</td><td class="max-w-md p-5 text-xs text-slate-500">{{ collect($log->metadata ?: [])->except(['body'])->map(fn ($value, $key) => $key.'='.$value)->implode(' · ') ?: 'Recorded action' }}</td></tr>@empty<tr><td colspan="5" class="p-10 text-center text-slate-500">No audit activity yet.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
