<?php

namespace App\Http\Controllers\Api;

use App\Models\CommunityQuestion;
use App\Models\KnowledgeArticle;
use App\Models\LiveMessage;
use App\Models\SupportEvent;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $ticket = null;
        if ($event->wasRecentlyCreated && in_array($data['event_type'], ['ticket.created', 'ticket.tracking.updated'], true)) {
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
        if ($event->wasRecentlyCreated && $data['event_type'] === 'ticket.reply.created') {
            $payload = $data['payload'];
            $ticket = SupportTicket::query()->where('reference', $payload['reference'] ?? null)->first();
            if ($ticket && filled($payload['body'] ?? null)) {
                $ticket->replies()->create([
                    'body' => (string) $payload['body'],
                    'is_internal' => (bool) ($payload['is_internal'] ?? false),
                    'author' => $payload['author'] ?? $payload['requester'] ?? ['name' => 'EcclesiaOS user'],
                ]);
            }
        }

        return response()->json(
            ['received' => true, 'duplicate' => ! $event->wasRecentlyCreated, 'id' => $event->id, 'ticket_id' => $ticket?->id],
            $event->wasRecentlyCreated ? 201 : 200,
        );
    }

    public function questions(Request $request): JsonResponse
    {
        $questions = CommunityQuestion::query()->where('status', 'published')->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search->where('title', 'like', '%'.$request->string('q').'%')->orWhere('body', 'like', '%'.$request->string('q').'%')))->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))->latest()->paginate(20);

        return response()->json(['data' => $questions->items(), 'meta' => ['current_page' => $questions->currentPage(), 'last_page' => $questions->lastPage(), 'total' => $questions->total()]]);
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
        $query = KnowledgeArticle::query()->where('published', true)->when($request->filled('q'), fn ($builder) => $builder->where(fn ($search) => $search->where('title', 'like', '%'.$request->string('q').'%')->orWhere('body', 'like', '%'.$request->string('q').'%')))->when($request->filled('category'), fn ($builder) => $builder->where('category', $request->string('category')));
        $articles = $query->latest()->paginate(20);

        return response()->json(['data' => $articles->items(), 'meta' => ['current_page' => $articles->currentPage(), 'last_page' => $articles->lastPage(), 'total' => $articles->total()], 'categories' => KnowledgeArticle::query()->where('published', true)->distinct()->pluck('category')->values()]);
    }

    public function live(Request $request): JsonResponse
    {
        $installation = $request->attributes->get('installation');
        $messages = LiveMessage::query()->where('installation_id', $installation->installation_id)->latest()->limit(50)->get();

        return response()->json(['online' => (bool) config('support.live_online', true), 'queue_position' => null, 'average_response' => config('support.average_response_minutes', 30), 'messages' => $messages, 'suggested_articles' => KnowledgeArticle::query()->where('published', true)->latest()->limit(3)->get()]);
    }

    public function liveMessage(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'min:2', 'max:5000'], 'author' => ['required', 'array']]);

        $installation = $request->attributes->get('installation');
        $message = LiveMessage::query()->create(['installation_id' => $installation->installation_id, 'author' => $data['author'], 'body' => $data['message'], 'status' => 'open']);

        return response()->json(['id' => (string) $message->id, 'received' => true, 'message' => $message->body], 201);
    }
}
