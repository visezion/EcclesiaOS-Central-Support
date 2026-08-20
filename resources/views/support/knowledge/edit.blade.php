@extends('layouts.app')
@section('title', 'Edit Article')
@section('content')
<div class="mx-auto max-w-4xl"><a href="{{ route('support.knowledge.show', $article) }}" class="text-sm font-bold text-violet-600 hover:text-violet-800">← Back to article</a><div class="mt-5"><p class="text-xs font-black uppercase tracking-[.18em] text-violet-600">Content management</p><h1 class="mt-2 text-3xl font-black text-slate-950">Edit article</h1><p class="mt-2 text-sm text-slate-500">Update the article content, category, or publication status.</p></div>@include('support.knowledge._form', ['action' => route('support.knowledge.update', $article), 'method' => 'PATCH', 'button' => 'Save changes', 'article' => $article])</div>
@endsection
