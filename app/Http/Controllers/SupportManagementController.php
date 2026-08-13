<?php

namespace App\Http\Controllers;

use App\Models\CommunityQuestion;
use App\Models\Installation;
use App\Models\KnowledgeArticle;
use App\Models\LiveMessage;
use App\Models\SupportTicket;
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

    public function updateTicket(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $ticket->update($request->validate(['subject' => ['required', 'string', 'max:180'], 'body' => ['required', 'string', 'max:20000'], 'status' => ['required', 'in:new,triaged,in_progress,waiting_on_church,resolved,closed'], 'priority' => ['required', 'in:low,normal,high,urgent']]));

        return back()->with('status', 'Ticket updated.');
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
