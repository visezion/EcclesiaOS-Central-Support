<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\CommunityQuestion;
use App\Models\KnowledgeArticle;
use App\Models\LiveMessage;
use App\Models\SupportEvent;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class SupportApiController
{
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

    private function articlePayload(KnowledgeArticle $article): array
    {
        return ['id' => (string) $article->id, 'slug' => $article->slug, 'title' => $article->title, 'excerpt' => Str::limit(strip_tags($article->body), 220), 'category_name' => $article->category, 'read_time' => max(1, (int) ceil(str_word_count(strip_tags($article->body)) / 200)).' minutes', 'helpful_percent' => 0, 'updated_human' => $article->updated_at?->diffForHumans(), 'body' => $article->body];
    }

    private function questionPayload(CommunityQuestion $question): array
    {
        return ['id' => (string) $question->id, 'category' => $question->category, 'status' => in_array($question->status, ['answered', 'solved'], true) ? 'solved' : 'open', 'title' => $question->title, 'excerpt' => Str::limit(strip_tags($question->body), 220), 'church_name' => data_get($question->church, 'display_name', data_get($question->church, 'name', 'EcclesiaOS church')), 'answers_count' => 0, 'helpful_count' => 0];
    }
}
