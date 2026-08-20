<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CommunityQuestion;
use App\Models\Installation;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\LiveMessage;
use App\Models\SupportTicket;
use App\Services\InstallationCallback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class SupportManagementController
{
    public function tickets(Request $request): View
    {
        $tickets = SupportTicket::query()
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search->where('reference', 'like', '%'.$request->string('q').'%')->orWhere('subject', 'like', '%'.$request->string('q').'%')->orWhere('installation_id', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->with('replies')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('support.tickets', ['tickets' => $tickets]);
    }

    public function storeTicket(Request $request): RedirectResponse
    {
        $data = $request->validate(['installation_id' => ['required', 'string', 'max:120'], 'subject' => ['required', 'string', 'max:180'], 'body' => ['required', 'string', 'max:20000'], 'priority' => ['required', 'in:low,normal,high,urgent']]);
        $ticket = SupportTicket::query()->create([...$data, 'reference' => 'SUP-'.strtoupper(Str::random(8)), 'requester' => ['name' => 'Central Support']]);
        $this->recordAudit('ticket.created', $ticket, ['reference' => $ticket->reference, 'installation_id' => $ticket->installation_id]);

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

        $this->recordAudit('ticket.updated', $ticket, ['reference' => $ticket->reference, 'status' => $ticket->status, 'installation_id' => $ticket->installation_id]);

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

        $this->recordAudit('ticket.reply.created', $ticket, ['reference' => $ticket->reference, 'internal' => $reply->is_internal, 'installation_id' => $ticket->installation_id]);

        return back()->with('status', 'Reply sent to EcclesiaOS.');
    }

    public function deleteTicket(SupportTicket $ticket): RedirectResponse
    {
        $ticket->delete();
        $this->recordAudit('ticket.deleted', $ticket, ['reference' => $ticket->reference]);

        return back()->with('status', 'Ticket deleted.');
    }

    public function community(): View
    {
        return view('support.community', ['questions' => CommunityQuestion::query()->latest()->paginate(20)]);
    }

    public function updateQuestion(Request $request, CommunityQuestion $question): RedirectResponse
    {
        $question->update($request->validate(['status' => ['required', 'in:pending_review,published,hidden,answered']]));
        $this->recordAudit('community_question.updated', $question, ['status' => $question->status]);

        return back()->with('status', 'Community question updated.');
    }

    public function deleteQuestion(CommunityQuestion $question): RedirectResponse
    {
        $question->delete();
        $this->recordAudit('community_question.deleted', $question);

        return back()->with('status', 'Community question deleted.');
    }

    public function knowledge(Request $request): View
    {
        $articles = KnowledgeArticle::query()
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search->where('title', 'like', '%'.$request->string('q').'%')->orWhere('category', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status') && in_array($request->string('status')->toString(), ['published', 'draft'], true), fn ($query) => $query->where('published', $request->string('status')->toString() === 'published'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('support.knowledge', ['articles' => $articles]);
    }

    public function createArticle(): View
    {
        return view('support.knowledge.create', ['categories' => KnowledgeCategory::query()->orderBy('name')->get()]);
    }

    public function showArticle(KnowledgeArticle $article): View
    {
        return view('support.knowledge.show', ['article' => $article]);
    }

    public function editArticle(KnowledgeArticle $article): View
    {
        return view('support.knowledge.edit', ['article' => $article, 'categories' => KnowledgeCategory::query()->orderBy('name')->get()]);
    }

    public function categories(): View
    {
        return view('support.knowledge.categories', ['categories' => KnowledgeCategory::query()->withCount('articles')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'unique:knowledge_categories,name']]);
        KnowledgeCategory::query()->create(['name' => $data['name'], 'slug' => Str::slug($data['name'])]);

        return back()->with('status', 'Knowledge category created.');
    }

    public function updateCategory(Request $request, KnowledgeCategory $category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'unique:knowledge_categories,name,'.$category->id]]);
        $oldName = $category->name;
        $category->update(['name' => $data['name'], 'slug' => Str::slug($data['name'])]);
        KnowledgeArticle::query()->where('category', $oldName)->update(['category' => $data['name']]);

        return back()->with('status', 'Knowledge category updated. Articles using it were updated too.');
    }

    public function deleteCategory(KnowledgeCategory $category): RedirectResponse
    {
        if ($category->articles()->exists()) {
            return back()->withErrors(['category' => 'This category still has articles. Move or delete those articles before deleting the category.']);
        }

        $category->delete();

        return back()->with('status', 'Knowledge category deleted.');
    }

    public function storeArticle(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'category' => ['required', 'string', 'max:80'], 'body' => ['required', 'string', 'max:50000']]);
        $article = KnowledgeArticle::query()->create([...$data, 'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)), 'published' => $request->boolean('published')]);
        $this->recordAudit('knowledge_article.created', $article, ['published' => $article->published]);

        return redirect()->route('support.knowledge.show', $article)->with('status', 'Knowledge article created.');
    }

    public function updateArticle(Request $request, KnowledgeArticle $article): RedirectResponse
    {
        $article->update([...$request->validate(['title' => ['required', 'string', 'max:180'], 'category' => ['required', 'string', 'max:80'], 'body' => ['required', 'string', 'max:50000']]), 'published' => $request->boolean('published')]);
        $this->recordAudit('knowledge_article.updated', $article, ['published' => $article->published]);

        return redirect()->route('support.knowledge.show', $article)->with('status', 'Knowledge article updated.');
    }

    public function deleteArticle(KnowledgeArticle $article): RedirectResponse
    {
        $article->delete();
        $this->recordAudit('knowledge_article.deleted', $article);

        return redirect()->route('support.knowledge')->with('status', 'Knowledge article deleted.');
    }

    public function live(): View
    {
        return view('support.live', ['messages' => LiveMessage::query()->latest()->paginate(30)]);
    }

    public function updateLive(Request $request, LiveMessage $message): RedirectResponse
    {
        $message->update($request->validate(['status' => ['required', 'in:open,assigned,resolved,closed']]));
        $this->recordAudit('live_message.updated', $message, ['status' => $message->status, 'installation_id' => $message->installation_id]);

        return back()->with('status', 'Live support message updated.');
    }

    public function connection(): View
    {
        return view('support.connection', ['installations' => Installation::query()->latest()->paginate(20)]);
    }

    public function registerInstallation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'installation_id' => ['required', 'string', 'max:120'],
            'church_name' => ['required', 'string', 'max:180'],
            'callback_url' => ['required', 'url', 'max:500', function (string $attribute, mixed $value, \Closure $fail): void {
                $parts = parse_url((string) $value);
                if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])) {
                    $fail('The callback URL must be a public URL without credentials, query parameters, or fragments.');
                }
                if (app()->environment('production') && strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
                    $fail('Production callback URLs must use HTTPS.');
                }
            }],
        ]);

        $token = Str::random(64);
        Installation::query()->updateOrCreate(
            ['installation_id' => $data['installation_id']],
            [
                'church_name' => $data['church_name'],
                'callback_url' => rtrim($data['callback_url'], '/'),
                'token_hash' => hash('sha256', $token),
                'token_encrypted' => encrypt($token),
                'enabled' => true,
            ],
        );

        AuditLog::query()->create(['user_id' => auth()->id(), 'action' => 'installation.registered', 'installation_id' => $data['installation_id'], 'metadata' => ['church_name' => $data['church_name']], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('installation_token', $token)->with('status', 'Installation registered and token created.');
    }

    public function exchangeGrant(Request $request, InstallationCallback $callback): RedirectResponse
    {
        $data = $request->validate(['installation_id' => ['required', 'exists:installations,installation_id'], 'grant_token' => ['required', 'size:64'], 'agent_id' => ['required', 'string', 'max:255'], 'agent_name' => ['required', 'string', 'max:255'], 'agent_email' => ['required', 'email', 'max:255']]);
        $installation = Installation::query()->where('installation_id', $data['installation_id'])->firstOrFail();
        try {
            $result = $callback->exchangeGrant($installation, collect($data)->except('installation_id')->all());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['remote' => 'The remote-support grant could not be exchanged. Check the callback URL, token, and grant expiry.']);
        }

        $this->recordAudit('remote_support.grant_exchanged', $installation, ['installation_id' => $installation->installation_id, 'agent_id' => $data['agent_id']]);

        return back()->with('remote_login_url', $result['login_url'] ?? null)->with('status', 'Remote support grant exchanged successfully.');
    }

    public function audit(): View
    {
        return view('support.audit', ['logs' => AuditLog::query()->latest()->paginate(30)->withQueryString()]);
    }

    private function recordAudit(string $action, object $model, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'installation_id' => $metadata['installation_id'] ?? ($model->installation_id ?? null),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
