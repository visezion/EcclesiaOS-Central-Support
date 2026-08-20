@extends('layouts.app')

@section('title', 'Live Support')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-violet-600">Support operations</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950">Live Support</h1>
            <p class="mt-2 text-sm text-slate-500">Respond to active church conversations from one focused workspace.</p>
        </div>
        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"><span class="size-2 rounded-full bg-emerald-500"></span>Support channel online</div>
    </div>

    @if(session('status'))<div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

    <div class="mt-7 grid min-h-[620px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:grid-cols-[300px_minmax(0,1fr)]">
        <aside class="border-b border-slate-200 bg-slate-50/70 lg:border-b-0 lg:border-r">
            <div class="border-b border-slate-200 px-4 py-4"><div class="flex items-center justify-between"><h2 class="text-sm font-black text-slate-950">Conversations</h2><span class="rounded-full bg-white px-2 py-1 text-[10px] font-bold text-slate-500">{{ $conversations->count() }}</span></div><p class="mt-1 text-xs text-slate-500">Select a church to continue the conversation.</p></div>
            <div class="max-h-[550px] space-y-1 overflow-y-auto p-2">
                @forelse($conversations as $conversation)
                    <a href="{{ route('support.live', ['installation_id' => $conversation['installation_id']]) }}" class="block rounded-xl px-3 py-3 transition {{ $selectedId === $conversation['installation_id'] ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-700 hover:bg-white' }}">
                        <div class="flex items-start justify-between gap-2"><div class="min-w-0"><div class="truncate text-sm font-bold">{{ $conversation['church_name'] }}</div><div class="mt-1 truncate text-[10px] {{ $selectedId === $conversation['installation_id'] ? 'text-violet-100' : 'text-slate-400' }}">{{ $conversation['installation_id'] }}</div></div>@if($conversation['unread'])<span class="grid size-5 shrink-0 place-items-center rounded-full {{ $selectedId === $conversation['installation_id'] ? 'bg-white text-violet-700' : 'bg-violet-600 text-white' }} text-[10px] font-black">{{ $conversation['unread'] }}</span>@endif</div>
                        <div class="mt-2 truncate text-xs {{ $selectedId === $conversation['installation_id'] ? 'text-violet-100' : 'text-slate-500' }}">{{ $conversation['latest']?->body }}</div>
                        <div class="mt-1 text-[10px] {{ $selectedId === $conversation['installation_id'] ? 'text-violet-200' : 'text-slate-400' }}">{{ $conversation['latest']?->created_at?->diffForHumans() }}</div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center"><i data-lucide="message-circle-off" class="mx-auto size-8 text-slate-300"></i><p class="mt-3 text-xs text-slate-500">No live conversations yet.</p></div>
                @endforelse
            </div>
        </aside>

        <section class="flex min-h-[620px] flex-col">
            @if($selected)
                <header class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center"><div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-violet-100 text-violet-700"><i data-lucide="church" class="size-5"></i></span><div><h2 class="text-sm font-black text-slate-950">{{ $selected['church_name'] }}</h2><p class="mt-0.5 text-[10px] text-slate-500">{{ $selected['installation_id'] }} · {{ $selected['messages']->count() }} messages</p></div></div><span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600"><span class="size-2 rounded-full bg-emerald-500"></span>Connected</span></header>
                <div class="flex-1 space-y-4 overflow-y-auto bg-slate-50/60 p-5">
                    @foreach($selected['messages'] as $message)
                        @php($agent = data_get($message->author, 'role', 'church') === 'agent')
                        <div class="flex {{ $agent ? 'justify-end' : 'justify-start' }}"><div class="max-w-[85%] {{ $agent ? 'items-end' : 'items-start' }}"><div class="mb-1 flex items-center gap-2 text-[10px] text-slate-400 {{ $agent ? 'justify-end' : '' }}"><span class="font-bold text-slate-600">{{ data_get($message->author, 'display_name', data_get($message->author, 'name', $agent ? 'Support' : 'Church')) }}</span><span>{{ $message->created_at?->format('M j, g:i A') }}</span></div><div class="rounded-2xl px-4 py-3 text-sm leading-6 {{ $agent ? 'bg-violet-600 text-white' : 'border border-slate-200 bg-white text-slate-700' }}">{{ $message->body }}</div></div></div>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('support.live.messages.store', $selected['installation_id']) }}" class="border-t border-slate-200 bg-white p-4">@csrf<div class="flex gap-2"><textarea name="body" required maxlength="5000" rows="2" placeholder="Write a clear reply to {{ $selected['church_name'] }}..." class="min-h-11 min-w-0 flex-1 resize-y rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></textarea><button class="self-end rounded-xl bg-violet-600 px-4 py-3 text-xs font-bold text-white shadow-sm hover:bg-violet-700">Send reply</button></div><p class="mt-2 text-[10px] text-slate-400">Keep replies concise and never request passwords or private credentials.</p></form>
            @else
                <div class="grid flex-1 place-items-center p-8 text-center"><div><span class="mx-auto grid size-14 place-items-center rounded-2xl bg-violet-50 text-violet-600"><i data-lucide="messages-square" class="size-7"></i></span><h2 class="mt-4 text-base font-black text-slate-950">Choose a conversation</h2><p class="mt-2 max-w-sm text-sm text-slate-500">Incoming live support conversations will appear here for fast, professional follow-up.</p></div></div>
            @endif
        </section>
    </div>
</div>
@endsection
