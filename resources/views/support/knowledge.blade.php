@extends('layouts.app')

@section('title', 'Knowledge Base')

@section('content')
<div class="mx-auto max-w-7xl">
    <h1 class="text-3xl font-black">Knowledge Base</h1>
    <p class="mt-2 text-sm text-slate-500">Create, edit, publish, and remove official support articles used by connected churches.</p>
    @if(session('status'))<div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
    <div class="mt-7 grid gap-6 xl:grid-cols-[.7fr_1.3fr]">
        <form method="POST" action="{{ route('support.knowledge.store') }}" class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-black">New article</h2>
            <div class="mt-4 space-y-3"><input name="title" required placeholder="Article title" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><input name="category" required placeholder="Category" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><textarea name="body" required rows="8" placeholder="Article content" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></textarea><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="published" value="1" class="rounded text-violet-600"> Publish now</label><button class="w-full rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white">Create article</button></div>
        </form>
        <div class="space-y-4">
            @forelse($articles as $article)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <form method="POST" action="{{ route('support.knowledge.update', $article) }}">
                        @csrf @method('PATCH')
                        <input name="title" value="{{ $article->title }}" class="w-full border-0 p-0 text-lg font-black focus:ring-0">
                        <input name="category" value="{{ $article->category }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
                        <textarea name="body" rows="5" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">{{ $article->body }}</textarea>
                        <div class="mt-3 flex items-center justify-between gap-3"><label class="text-xs"><input type="checkbox" name="published" value="1" @checked($article->published)> Published</label><button class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-bold text-white">Save</button></div>
                    </form>
                    <form method="POST" action="{{ route('support.knowledge.delete', $article) }}" class="mt-2 text-right">@csrf @method('DELETE')<button class="text-xs font-bold text-rose-600">Delete article</button></form>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500">No articles yet.</div>
            @endforelse
            {{ $articles->links() }}
        </div>
    </div>
</div>
@endsection
