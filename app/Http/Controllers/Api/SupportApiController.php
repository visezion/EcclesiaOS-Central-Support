<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\CommunityQuestion;
use App\Models\Installation;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleFeedback;
use App\Models\LiveMessage;
use App\Models\SupportEvent;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SupportApiController
{
    public function enroll(Request $request): JsonResponse
    {
        $configuredKey = (string) config('support.enrollment_key');
        abort_unless($configuredKey !== '' && hash_equals($configuredKey, (string) $request->header('X-EcclesiaOS-Enrollment-Key')), 401, 'Invalid enrollment key.');

        $data = $request->validate([
            'installation_id' => ['required', 'uuid'],
            'church_name' => ['required', 'string', 'max:180'],
            'callback_url' => ['required', 'url', 'max:500'],
            'version' => ['nullable', 'string', 'max:40'],
        ]);
        $parts = parse_url($data['callback_url']);
        abort_unless(! isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment']), 422, 'The callback URL must not contain credentials, query parameters, or fragments.');
        if (app()->environment('production')) {
            abort_unless(strtolower((string) ($parts['scheme'] ?? '')) === 'https', 422, 'Production callback URLs must use HTTPS.');
        }

        $installation = Installation::query()->where('installation_id', $data['installation_id'])->first();
        $token = $installation && filled($installation->token_encrypted) ? rescue(fn (): string => Crypt::decryptString($installation->token_encrypted), '', false) : '';
        if ($token === '') {
            $token = config('support.installation_token_prefix', 'eco_').Str::random(56);
        }

        $installation = Installation::query()->updateOrCreate(
            ['installation_id' => $data['installation_id']],
            ['church_name' => $data['church_name'], 'callback_url' => rtrim($data['callback_url'], '/'), 'version' => $data['version'] ?? null, 'token_hash' => hash('sha256', $token), 'token_encrypted' => Crypt::encryptString($token), 'enabled' => true, 'last_seen_at' => now()],
        );
        AuditLog::query()->create(['action' => 'installation.auto_enrolled', 'installation_id' => $installation->installation_id, 'metadata' => ['church_name' => $installation->church_name, 'version' => $installation->version, 'source' => 'ecclesiaos_installer'], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        Log::info('EcclesiaOS installation auto-enrolled with Central Support.', ['installation_id' => $installation->installation_id]);

        return response()->json(['connected' => true, 'installation_id' => $installation->installation_id, 'api_token' => $token, 'endpoint' => rtrim((string) config('app.url'), '/')]);
    }

    public function ping(): JsonResponse
    {
        return response()->json(['message' => 'Central support connection is healthy.', 'service' => 'EcclesiaOS Central Support']);
    }

    public function event(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'uuid'],
            'event_type' => ['required', 'string', 'max:100'],
            'occurred_at' => ['nullable', 'date'],
            'payload' => ['required', 'array'],
        ]);
        $installation = $request->attributes->get('installation');
        $event = SupportEvent::query()->firstOrCreate(
            ['event_id' => $data['event_id']],
            [...$data, 'installation_id' => $installation->installation_id],
        );
        if ($event->wasRecentlyCreated) {
            AuditLog::query()->create(['action' => 'api.event.received', 'installation_id' => $installation->installation_id, 'metadata' => ['event_type' => $data['event_type'], 'event_id' => $data['event_id']]]);
        }
        $ticket = null;
        if ($event->wasRecentlyCreated && $data['event_type'] === 'ticket.created') {
            $payload = $data['payload'];
            $ticket = SupportTicket::query()->updateOrCreate(
                ['reference' => (string) ($payload['reference'] ?? 'SUP-'.$data['event_id'])],
                [
                    'installation_id' => $installation->installation_id,
                    'category' => $payload['category'] ?? null,
                    'subject' => (string) ($payload['subject'] ?? 'EcclesiaOS support ticket'),
                    'body' => (string) ($payload['description'] ?? $payload['body'] ?? 'No description provided.'),
                    'expected_outcome' => $payload['expected_outcome'] ?? null,
                    'page_url' => $payload['page_url'] ?? null,
                    'browser' => $payload['browser'] ?? null,
                    'requester' => $payload['reporter'] ?? null,
                    'status' => (string) ($payload['status'] ?? 'new'),
                    'priority' => (string) ($payload['priority'] ?? 'normal'),
                    'progress' => (int) ($payload['progress'] ?? 5),
                ],
            );
        }
        if ($event->wasRecentlyCreated && $data['event_type'] === 'ticket.tracking.updated') {
            $payload = $data['payload'];
            $ticket = SupportTicket::query()->where('public_id', $payload['central_ticket_id'] ?? null)->orWhere('reference', $payload['reference'] ?? null)->first();
            if ($ticket) {
                $ticket->update(collect($payload)->only(['status', 'priority', 'progress'])->all());
            }
        }
        if ($event->wasRecentlyCreated && $data['event_type'] === 'ticket.reply.created') {
            $payload = $data['payload'];
            $ticket = SupportTicket::query()->where('public_id', $payload['central_ticket_id'] ?? null)->orWhere('reference', $payload['reference'] ?? null)->first();
            $reply = $payload['reply'] ?? $payload;
            if ($ticket && filled($reply['body'] ?? null)) {
                $ticket->replies()->create([
                    'body' => (string) $reply['body'],
                    'is_internal' => (bool) ($reply['is_internal'] ?? false),
                    'author' => $reply['author'] ?? ['name' => $reply['author_name'] ?? 'EcclesiaOS user'],
                ]);
            }
        }

        return response()->json(
            ['received' => true, 'duplicate' => ! $event->wasRecentlyCreated, 'id' => $event->id, 'ticket_id' => $ticket?->public_id],
            $event->wasRecentlyCreated ? 201 : 200,
        );
    }

    public function questions(Request $request): JsonResponse
    {
        $query = CommunityQuestion::query()->where('status', 'published')->when($request->filled('q'), fn ($builder) => $builder->where(fn ($search) => $search->where('title', 'like', '%'.$request->string('q').'%')->orWhere('body', 'like', '%'.$request->string('q').'%')))->when($request->filled('category'), fn ($builder) => $builder->where('category', $request->string('category')))->latest();
        $questions = $query->get();
        $page = max(1, $request->integer('page', 1));
        $items = $questions->forPage($page, 20)->values()->map(fn (CommunityQuestion $question): array => $this->questionPayload($question));

        return response()->json(['data' => $items, 'meta' => [
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($questions->count() / 20)),
            'total' => $questions->count(),
            'solved' => CommunityQuestion::query()->whereIn('status', ['answered', 'solved'])->count(),
            'open' => CommunityQuestion::query()->whereIn('status', ['published', 'pending_review'])->count(),
            'official_answers' => CommunityQuestion::query()->whereIn('status', ['answered', 'solved'])->count(),
            'helpful' => 0,
        ]]);
    }

    public function createQuestion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'min:10', 'max:180'],
            'body' => ['required', 'string', 'min:20', 'max:20000'],
            'consent' => ['accepted'],
            'author' => ['required', 'array'],
            'church' => ['required', 'array'],
        ]);
        $data['installation_id'] = $request->attributes->get('installation')->installation_id;
        $data['status'] = 'pending_review';

        return response()->json(CommunityQuestion::query()->create($data), 201);
    }

    public function knowledge(Request $request): JsonResponse
    {
        $articles = KnowledgeArticle::query()->where('published', true)->when($request->filled('q'), fn ($builder) => $builder->where(fn ($search) => $search->where('title', 'like', '%'.$request->string('q').'%')->orWhere('body', 'like', '%'.$request->string('q').'%')))->latest()->orderBy('id')->get();
        if ($request->filled('category')) {
            $category = Str::slug($request->string('category')->toString());
            $articles = $articles->filter(fn (KnowledgeArticle $article): bool => Str::slug($article->category) === $category)->values();
        }
        $page = max(1, $request->integer('page', 1));
        $items = $articles->forPage($page, 20)->values()->map(fn (KnowledgeArticle $article): array => $this->articlePayload($article));
        $categories = KnowledgeArticle::query()->where('published', true)->latest()->orderBy('id')->get()->groupBy('category')->map(fn (Collection $items, string $name): array => ['slug' => Str::slug($name), 'name' => $name, 'articles_count' => $items->count()])->values();

        return response()->json(['data' => $items, 'meta' => ['current_page' => $page, 'last_page' => max(1, (int) ceil($articles->count() / 20)), 'total' => $articles->count()], 'categories' => $categories]);
    }

    public function article(string $article): JsonResponse
    {
        $knowledgeArticle = $this->publishedArticle($article);

        return response()->json(['data' => $this->articlePayload($knowledgeArticle, true)]);
    }

    public function rateArticle(Request $request, string $article): JsonResponse
    {
        $knowledgeArticle = $this->publishedArticle($article);
        $data = $request->validate([
            'helpful' => ['required', 'boolean'],
            'voter.local_id' => ['nullable', 'string', 'max:180'],
        ]);
        $installation = $request->attributes->get('installation');
        $voterId = (string) ($data['voter']['local_id'] ?? 'installation');

        KnowledgeArticleFeedback::query()->updateOrCreate(
            [
                'knowledge_article_id' => $knowledgeArticle->id,
                'installation_id' => $installation->installation_id,
                'voter_id' => $voterId !== '' ? $voterId : 'installation',
            ],
            ['helpful' => (bool) $data['helpful']],
        );

        return response()->json([
            'helpful' => (bool) $data['helpful'],
            'helpful_percent' => $this->helpfulPercent($knowledgeArticle),
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        $installation = $request->attributes->get('installation');
        $messages = LiveMessage::query()->where('installation_id', $installation->installation_id)->latest()->limit(50)->get()->reverse()->values()->map(fn (LiveMessage $message): array => [
            'id' => (string) $message->id,
            'body' => $message->body,
            'mine' => false,
            'status' => $message->status,
            'sent_at' => $message->created_at?->toIso8601String(),
            'author' => $message->author,
        ]);

        return response()->json(['online' => (bool) config('support.live_online', true), 'agents_online' => config('support.live_online', true) ? 1 : 0, 'agent' => ['name' => 'Central Support'], 'queue_position' => null, 'average_response' => config('support.average_response_minutes', 30).' minutes', 'messages' => $messages, 'suggested_articles' => KnowledgeArticle::query()->where('published', true)->latest()->limit(3)->get()->map(fn (KnowledgeArticle $article): array => $this->articlePayload($article))]);
    }

    public function liveMessage(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'min:2', 'max:5000'], 'author' => ['required', 'array']]);

        $installation = $request->attributes->get('installation');
        $message = LiveMessage::query()->create(['installation_id' => $installation->installation_id, 'author' => $data['author'], 'body' => $data['message'], 'status' => 'open']);

        return response()->json(['id' => (string) $message->id, 'message_id' => (string) $message->id, 'received' => true, 'message' => $message->body], 201);
    }

    private function articlePayload(KnowledgeArticle $article, bool $includeBody = false): array
    {
        $payload = ['id' => (string) $article->id, 'slug' => $article->slug, 'title' => $article->title, 'excerpt' => Str::limit(strip_tags($article->body), 220), 'category_name' => $article->category, 'read_time' => max(1, (int) ceil(str_word_count(strip_tags($article->body)) / 200)).' minutes', 'helpful_percent' => $this->helpfulPercent($article), 'updated_human' => $article->updated_at?->diffForHumans()];
        if ($includeBody) {
            $payload['body'] = $article->body;
        }

        return $payload;
    }

    private function publishedArticle(string $article): KnowledgeArticle
    {
        return KnowledgeArticle::query()
            ->where('published', true)
            ->where(fn ($query) => $query->whereKey($article)->orWhere('slug', $article))
            ->firstOrFail();
    }

    private function helpfulPercent(KnowledgeArticle $article): int
    {
        $votes = KnowledgeArticleFeedback::query()->where('knowledge_article_id', $article->id)->get();
        if ($votes->isEmpty()) {
            return 0;
        }

        return (int) round($votes->where('helpful', true)->count() / $votes->count() * 100);
    }

    private function questionPayload(CommunityQuestion $question): array
    {
        return ['id' => (string) $question->id, 'category' => $question->category, 'status' => in_array($question->status, ['answered', 'solved'], true) ? 'solved' : 'open', 'title' => $question->title, 'excerpt' => Str::limit(strip_tags($question->body), 220), 'church_name' => data_get($question->church, 'display_name', data_get($question->church, 'name', 'EcclesiaOS church')), 'answers_count' => 0, 'helpful_count' => 0];
    }
}
