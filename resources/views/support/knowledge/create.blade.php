@extends('layouts.app')
@section('title', 'Create Article')
@section('content')
<div class="mx-auto max-w-4xl"><a href="{{ route('support.knowledge') }}" class="text-sm font-bold text-violet-600 hover:text-violet-800">← Back to Knowledge Base</a><div class="mt-5"><p class="text-xs font-black uppercase tracking-[.18em] text-violet-600">New content</p><h1 class="mt-2 text-3xl font-black text-slate-950">Create article</h1><p class="mt-2 text-sm text-slate-500">Write clear, practical guidance for church staff, volunteers, and members.</p></div>@include('support.knowledge._form', ['action' => route('support.knowledge.store'), 'method' => 'POST', 'button' => 'Create article'])</div>
@endsection
