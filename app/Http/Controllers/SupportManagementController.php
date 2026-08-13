<?php

namespace App\Http\Controllers;

use App\Models\CommunityQuestion;
use App\Models\Installation;
use App\Models\KnowledgeArticle;
use App\Models\LiveMessage;
use App\Models\SupportTicket;
use App\Services\InstallationCallback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class SupportManagementController
{
    public function tickets(): View
    {
        return view('support.tickets', ['tickets' => SupportTicket::query()->latest()->paginate(20)]);
    }

    public function storeTicket(Request $request): RedirectResponse
    {
        $data = $request->validate(['installation_id' => ['required', 'string', 'max:120'], 'subject' => ['required', 'string', 'max:180'], 'body' => ['required', 'string', 'max:20000'], 'priority' => ['required', 'in:low,normal,high,urgent']]);
        SupportTicket::query()->create([...$data, 'reference' => 'SUP-'.strtoupper(Str::random(8)), 'requester' => ['name' => 'Central Support']]);

        return back()->with('status', 'Ticket created.');
    }

    public function updateTicket(Request $request, SupportTicket $ticket, InstallationCallback $callback): RedirectResponse
    {
        $ticket->update($request->validate(['subject' => ['required', 'string', 'max:180'], 'body' => ['required', 'string', 'max:20000'], 'status' => ['required', 'in:new,triaged,in_progress,waiting_on_church,resolved,closed'], 'priority' => ['required', 'in:low,normal,high,urgent']]));
        $installation = Installation::query()->where('installation_id', $ticket->installation_id)->firstOrFail();
        try {
            $callback->ticketUpdated($installation, $ticket);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['ticket' => 'Ticket saved locally, but the update could not be delivered to EcclesiaOS.']);
        }

        return back()->with('status', 'Ticket updated.');
    }

    public function replyTicket(Request $request, SupportTicket $ticket, InstallationCallback $callback): RedirectResponse
    {
        $reply = $ticket->replies()->create($request->validate(['body' => ['required', 'string', 'max:10000'], 'is_internal' => ['nullable', 'boolean']]) + ['author' => ['name' => $request->user()->name], 'is_internal' => $request->boolean('is_internal')]);
        $installation = Installation::query()->where('installation_id', $ticket->installation_id)->firstOrFail();
        try {
            $callback->replyCreated($installation, $ticket, $reply);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['reply' => 'Reply saved locally, but it could not be delivered to EcclesiaOS.']);
        }

        return back()->with('status', 'Reply sent to EcclesiaOS.');
    }

    public function deleteTicket(SupportTicket $ticket): RedirectResponse
    {
        $ticket->delete();

        return back()->with('status', 'Ticket deleted.');
    }

    public function community(): View
    {
        return view('support.community', ['questions' => CommunityQuestion::query()->latest()->paginate(20)]);
    }

    public function updateQuestion(Request $request, CommunityQuestion $question): RedirectResponse
    {
        $question->update($request->validate(['status' => ['required', 'in:pending_review,published,hidden,answered']]));

        return back()->with('status', 'Community question updated.');
    }

    public function deleteQuestion(CommunityQuestion $question): RedirectResponse
    {
        $question->delete();

        return back()->with('status', 'Community question deleted.');
    }

    public function knowledge(): View
    {
        return view('support.knowledge', ['articles' => KnowledgeArticle::query()->latest()->paginate(20)]);
    }

    public function storeArticle(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'category' => ['required', 'string', 'max:80'], 'body' => ['required', 'string', 'max:50000']]);
        KnowledgeArticle::query()->create([...$data, 'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)), 'published' => $request->boolean('published')]);

        return back()->with('status', 'Knowledge article created.');
    }

    public function updateArticle(Request $request, KnowledgeArticle $article): RedirectResponse
    {
        $article->update([...$request->validate(['title' => ['required', 'string', 'max:180'], 'category' => ['required', 'string', 'max:80'], 'body' => ['required', 'string', 'max:50000']]), 'published' => $request->boolean('published')]);

        return back()->with('status', 'Knowledge article updated.');
    }

    public function deleteArticle(KnowledgeArticle $article): RedirectResponse
    {
        $article->delete();

        return back()->with('status', 'Knowledge article deleted.');
    }

    public function live(): View
    {
        return view('support.live', ['messages' => LiveMessage::query()->latest()->paginate(30)]);
    }

    public function updateLive(Request $request, LiveMessage $message): RedirectResponse
    {
        $message->update($request->validate(['status' => ['required', 'in:open,assigned,resolved,closed']]));

        return back()->with('status', 'Live support message updated.');
    }

    public function connection(): View
    {
        return view('support.connection', ['installations' => Installation::query()->latest()->paginate(20)]);
    }
}
