@extends('layouts.app')

@section('title', 'Central Connection')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-violet-600">Connected churches</p>
        <h1 class="mt-1 text-3xl font-black">Central Connection</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Register an EcclesiaOS installation, rotate its private token, and exchange approved temporary remote-support grants.</p>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if(session('installation_token'))
        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5">
            <p class="text-sm font-bold text-amber-900">Copy this installation token now</p>
            <code class="mt-3 block overflow-x-auto rounded-xl bg-white px-4 py-3 text-sm text-slate-800">{{ session('installation_token') }}</code>
            <p class="mt-2 text-xs text-amber-800">It is shown once. Store it securely in the church’s Central Connection settings.</p>
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif
    @if(session('remote_login_url'))
        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">Remote session ready: <a class="font-bold underline" href="{{ session('remote_login_url') }}" target="_blank" rel="noopener">Open secure support session</a></div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ route('support.connection.installations.store') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-black">Register or rotate an installation</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Use the callback URL of that church’s public EcclesiaOS installation. Rotating replaces the previous token.</p>
            <div class="mt-5 space-y-3">
                <input name="installation_id" required placeholder="Installation ID" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                <input name="church_name" required placeholder="Church name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                <input type="url" name="callback_url" required placeholder="https://church.example.org" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                <button class="w-full rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white hover:bg-violet-700">Register installation</button>
            </div>
        </form>

        <form method="POST" action="{{ route('support.remote.exchange') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-black">Open temporary remote support</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Paste a grant created by the church administrator. Grants should be short-lived and single-use.</p>
            <div class="mt-5 space-y-3">
                <select name="installation_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                    <option value="">Select installation</option>
                    @foreach($installations as $installation)
                        <option value="{{ $installation->installation_id }}">{{ $installation->church_name ?: $installation->installation_id }}</option>
                    @endforeach
                </select>
                <input name="grant_token" required minlength="64" maxlength="64" placeholder="64-character grant token" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                <div class="grid gap-3 sm:grid-cols-2">
                    <input name="agent_id" required placeholder="Agent ID" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                    <input name="agent_name" required placeholder="Agent name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                </div>
                <input type="email" name="agent_email" required placeholder="Agent email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                <button class="w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white hover:bg-slate-800">Exchange grant</button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5"><h2 class="font-black">Installations</h2><p class="mt-1 text-sm text-slate-500">Tokens are stored encrypted and can be revoked from the overview.</p></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400"><tr><th class="p-5">Church</th><th class="p-5">Installation ID</th><th class="p-5">Callback</th><th class="p-5">Version</th><th class="p-5">Last seen</th><th class="p-5">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($installations as $installation)
                        <tr><td class="p-5 font-semibold">{{ $installation->church_name ?: 'Unnamed church' }}</td><td class="p-5 text-slate-500">{{ $installation->installation_id }}</td><td class="max-w-xs truncate p-5 text-slate-500">{{ $installation->callback_url ?: 'Not configured' }}</td><td class="p-5 text-slate-500">{{ $installation->version ?: '—' }}</td><td class="p-5 text-slate-500">{{ $installation->last_seen_at?->diffForHumans() ?: 'Never' }}</td><td class="p-5"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $installation->enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $installation->enabled ? 'Enabled' : 'Disabled' }}</span></td></tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-slate-500">No installations connected.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $installations->links() }}
</div>
@endsection
