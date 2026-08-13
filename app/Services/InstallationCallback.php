<?php

namespace App\Services;

use App\Models\Installation;
use App\Models\SupportReply;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class InstallationCallback
{
    public function exchangeGrant(Installation $installation, array $payload): array
    {
        $token = $this->token($installation);
        if ($installation->callback_url === null || $token === '') {
            throw new RuntimeException('This installation has no callback URL or usable token.');
        }
        $url = rtrim((string) $installation->callback_url, '/').'/support/central-access/exchange';
        $response = Http::acceptJson()->asJson()->withToken($token)->connectTimeout(5)->timeout(20)->post($url, $payload);
        if ($response->failed()) {
            throw new RuntimeException('EcclesiaOS remote-support exchange returned HTTP '.$response->status().'.');
        }

        return (array) $response->json();
    }

    public function ticketUpdated(Installation $installation, SupportTicket $ticket): void
    {
        $this->send($installation, [
            'event_id' => (string) Str::uuid(),
            'event_type' => 'ticket.updated',
            'ticket_id' => (string) $ticket->id,
            'reference' => $ticket->reference,
            'payload' => ['status' => $ticket->status, 'priority' => $ticket->priority, 'progress' => $ticket->progress, 'agent_name' => auth()->user()?->name],
        ]);
    }

    public function replyCreated(Installation $installation, SupportTicket $ticket, SupportReply $reply): void
    {
        if ($reply->is_internal) {
            return;
        }
        $this->send($installation, [
            'event_id' => (string) Str::uuid(),
            'event_type' => 'ticket.reply.created',
            'ticket_id' => (string) $ticket->id,
            'reference' => $ticket->reference,
            'payload' => ['body' => $reply->body, 'is_internal' => false, 'agent_name' => auth()->user()?->name],
        ]);
    }

    private function send(Installation $installation, array $payload): void
    {
        $token = $this->token($installation);
        if ($installation->callback_url === null || $token === '') {
            throw new RuntimeException('This installation has no callback URL or usable token. Rotate its installation token first.');
        }
        $url = rtrim((string) $installation->callback_url, '/').'/support/central-events';
        $response = Http::acceptJson()->asJson()->withToken($token)->connectTimeout(5)->timeout(20)->post($url, $payload);
        if ($response->failed()) {
            throw new RuntimeException('EcclesiaOS callback returned HTTP '.$response->status().'.');
        }
    }

    private function token(Installation $installation): string
    {
        if (blank($installation->token_encrypted)) {
            return '';
        }
        try {
            return Crypt::decryptString($installation->token_encrypted);
        } catch (\Throwable) {
            return '';
        }
    }
}
