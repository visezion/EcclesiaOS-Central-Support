@extends('layouts.app')

@section('title', 'Knowledge Base')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-black uppercase tracking-[.18em] text-violet-600">Content library</p><h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Knowledge Base</h1><p class="mt-2 max-w-2xl text-sm text-slate-500">Create and maintain the official guidance shared with churches and their members.</p></div>
        <a href="{{ route('support.knowledge.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-3 text-sm font-black text-white shadow-sm transition hover:bg-violet-700"><span class="text-lg leading-none">+</span> New article</a>
    </div>
    @if(session('status'))<div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif
    <div class="mt-7 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><form method="GET" class="flex flex-col gap-3 md:flex-row"><input name="q" value="{{ request('q') }}" placeholder="Search articles or categories..." class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100"><select name="status" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"><option value="">All statuses</option><option value="published" @selected(request('status') === 'published')>Published</option><option value="draft" @selected(request('status') === 'draft')>Drafts</option></select><button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white">Search</button>@if(request()->hasAny(['q', 'status']))<a href="{{ route('support.knowledge') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-slate-600">Clear</a>@endif</form></div>
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="hidden grid-cols-[1fr_180px_120px_150px] gap-4 border-b border-slate-100 bg-slate-50 px-6 py-3 text-[11px] font-black uppercase tracking-wider text-slate-400 md:grid"><span>Article</span><span>Category</span><span>Status</span><span class="text-right">Actions</span></div>
        @forelse($articles as $article)
            <div class="grid gap-4 border-b border-slate-100 px-5 py-5 last:border-0 md:grid-cols-[1fr_180px_120px_150px] md:items-center md:px-6"><div class="min-w-0"><a href="{{ route('support.knowledge.show', $article) }}" class="block truncate text-base font-black text-slate-950 hover:text-violet-700">{{ $article->title }}</a><p class="mt-1 truncate text-xs text-slate-500">Updated {{ $article->updated_at?->diffForHumans() }}</p></div><div><span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700">{{ $article->category }}</span></div><div><span class="inline-flex items-center gap-1.5 text-xs font-bold {{ $article->published ? 'text-emerald-600' : 'text-slate-500' }}"><span class="size-2 rounded-full {{ $article->published ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>{{ $article->published ? 'Published' : 'Draft' }}</span></div><div class="flex items-center justify-start gap-3 md:justify-end"><a href="{{ route('support.knowledge.show', $article) }}" class="text-xs font-bold text-slate-600 hover:text-violet-700">View</a><a href="{{ route('support.knowledge.edit', $article) }}" class="text-xs font-bold text-violet-600 hover:text-violet-800">Edit</a><form method="POST" action="{{ route('support.knowledge.delete', $article) }}" onsubmit="return confirm('Delete this article permanently?')">@csrf @method('DELETE')<button class="text-xs font-bold text-rose-600 hover:text-rose-800">Delete</button></form></div></div>
        @empty
            <div class="px-6 py-14 text-center"><div class="text-3xl">◌</div><p class="mt-3 font-bold text-slate-700">No articles found</p><p class="mt-1 text-sm text-slate-500">Create your first support article to start the library.</p></div>
        @endforelse
    </div>
    <div class="mt-5">{{ $articles->links() }}</div>
</div>
@endsection
