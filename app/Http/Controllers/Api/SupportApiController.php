<?php

namespace App\Http\Controllers\Api;

use App\Models\CommunityQuestion;
use App\Models\SupportEvent;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $ticket = null;
        if ($event->wasRecentlyCreated && in_array($data['event_type'], ['ticket.created', 'ticket.tracking.updated'], true)) {
            $payload = $data['payload'];
            $ticket = SupportTicket::query()->updateOrCreate(
                ['reference' => (string) ($payload['reference'] ?? 'SUP-'.$data['event_id'])],
                [
                    'installation_id' => $installation->installation_id,
                    'subject' => (string) ($payload['subject'] ?? 'EcclesiaOS support ticket'),
                    'body' => (string) ($payload['description'] ?? $payload['body'] ?? 'No description provided.'),
                    'requester' => $payload['reporter'] ?? null,
                    'status' => (string) ($payload['status'] ?? 'new'),
                    'priority' => (string) ($payload['priority'] ?? 'normal'),
                ],
            );
        }

        return response()->json(
            ['received' => true, 'duplicate' => ! $event->wasRecentlyCreated, 'id' => $event->id, 'ticket_id' => $ticket?->id],
            $event->wasRecentlyCreated ? 201 : 200,
        );
    }

    public function questions(Request $request): JsonResponse
    {
        $questions = CommunityQuestion::query()->latest()->paginate(20);

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

    public function knowledge(): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0], 'categories' => []]);
    }

    public function live(): JsonResponse
    {
        return response()->json(['online' => false, 'queue_position' => null, 'average_response' => null, 'messages' => [], 'suggested_articles' => []]);
    }

    public function liveMessage(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'min:2', 'max:5000'], 'author' => ['required', 'array']]);

        return response()->json(['id' => (string) Str::uuid(), 'received' => true, 'message' => $data['message']], 201);
    }
}
