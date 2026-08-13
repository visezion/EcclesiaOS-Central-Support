@extends('layouts.app')

@section('title', 'My Tickets')

@section('content')
<div class="mx-auto max-w-7xl">
    <h1 class="text-3xl font-black">My Tickets</h1>
    <p class="mt-2 text-sm text-slate-500">Create, edit, reply to, track, and close support requests.</p>
    @if(session('status'))<div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
    <div class="mt-7 grid gap-6 xl:grid-cols-[.7fr_1.3fr]">
        <form method="POST" action="{{ route('support.tickets.store') }}" class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf<h2 class="font-black">Create ticket</h2>
            <div class="mt-4 space-y-3"><input name="installation_id" required placeholder="Installation ID" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><input name="subject" required placeholder="Subject" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><textarea name="body" required rows="5" placeholder="Describe the issue" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></textarea><select name="priority" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><option>normal</option><option>low</option><option>high</option><option>urgent</option></select><button class="w-full rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white">Create ticket</button></div>
        </form>
        <div class="space-y-4">
            @forelse($tickets as $ticket)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <form method="POST" action="{{ route('support.tickets.update', $ticket) }}">@csrf @method('PATCH')<div class="flex flex-wrap items-center justify-between gap-3"><div><span class="text-xs font-bold text-violet-600">{{ $ticket->reference }}</span><input name="subject" value="{{ $ticket->subject }}" class="mt-1 block w-full border-0 p-0 text-lg font-black focus:ring-0"></div><span class="text-xs text-slate-400">{{ $ticket->created_at->diffForHumans() }}</span></div><textarea name="body" rows="3" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">{{ $ticket->body }}</textarea><div class="mt-3 flex flex-wrap gap-2"><select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-xs"><option @selected($ticket->status==='new')>new</option><option @selected($ticket->status==='triaged')>triaged</option><option @selected($ticket->status==='in_progress')>in_progress</option><option @selected($ticket->status==='waiting_on_church')>waiting_on_church</option><option @selected($ticket->status==='resolved')>resolved</option><option @selected($ticket->status==='closed')>closed</option></select><select name="priority" class="rounded-lg border border-slate-200 px-3 py-2 text-xs"><option @selected($ticket->priority==='low')>low</option><option @selected($ticket->priority==='normal')>normal</option><option @selected($ticket->priority==='high')>high</option><option @selected($ticket->priority==='urgent')>urgent</option></select><button class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-bold text-white">Save and sync</button></div></form>
                    <div class="mt-4 border-t border-slate-100 pt-4"><form method="POST" action="{{ route('support.tickets.replies.store', $ticket) }}">@csrf<div class="flex gap-2"><input name="body" required placeholder="Reply to the church..." class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm"><button class="rounded-lg bg-violet-600 px-3 py-2 text-xs font-bold text-white">Send reply</button></div><label class="mt-2 flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" name="is_internal" value="1" class="rounded text-violet-600"> Internal note only</label></form></div>
                    <div class="mt-3 flex justify-end"><form method="POST" action="{{ route('support.tickets.delete', $ticket) }}">@csrf @method('DELETE')<button class="text-xs font-bold text-rose-600">Delete ticket</button></form></div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500">No tickets yet. New EcclesiaOS submissions will appear here after synchronization.</div>
            @endforelse
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection
