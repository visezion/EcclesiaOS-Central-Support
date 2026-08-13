<?php

namespace Tests\Feature;

use App\Models\Installation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SupportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_installation_can_ping_and_sync_an_event_once(): void
    {
        $token = 'eco_'.Str::random(56);
        $installation = Installation::query()->create([
            'installation_id' => 'install-test',
            'church_name' => 'Test Church',
            'token_hash' => hash('sha256', $token),
        ]);
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-EcclesiaOS-Installation' => $installation->installation_id,
            'X-EcclesiaOS-Version' => '1.0.31',
        ];
        $event = ['event_id' => (string) Str::uuid(), 'event_type' => 'ticket.created', 'payload' => ['reference' => 'SUP-TEST123', 'subject' => 'Cannot send email', 'description' => 'The church email test is failing.', 'reporter' => ['name' => 'Church Admin'], 'status' => 'new', 'priority' => 'high']];

        $this->withHeaders($headers)->getJson('/api/v1/installations/ping')->assertOk()->assertJsonPath('service', 'EcclesiaOS Central Support');
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $event)->assertCreated()->assertJsonPath('duplicate', false);
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $event)->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('support_events', 1);
        $this->assertDatabaseHas('support_tickets', ['reference' => 'SUP-TEST123', 'subject' => 'Cannot send email', 'priority' => 'high']);
        $this->assertDatabaseHas('installations', ['installation_id' => 'install-test', 'version' => '1.0.31']);
    }

    public function test_invalid_installation_credentials_are_rejected(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer invalid',
            'X-EcclesiaOS-Installation' => 'unknown',
        ])->getJson('/api/v1/installations/ping')->assertUnauthorized();
    }

    public function test_ticket_reply_events_are_synced_idempotently(): void
    {
        $token = 'eco_'.Str::random(56);
        $installation = Installation::query()->create([
            'installation_id' => 'install-reply',
            'church_name' => 'Reply Church',
            'token_hash' => hash('sha256', $token),
        ]);
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-EcclesiaOS-Installation' => $installation->installation_id,
        ];
        $ticketEvent = ['event_id' => (string) Str::uuid(), 'event_type' => 'ticket.created', 'payload' => ['reference' => 'SUP-REPLY123', 'subject' => 'Need help', 'description' => 'Please help.']];
        $replyEvent = ['event_id' => (string) Str::uuid(), 'event_type' => 'ticket.reply.created', 'payload' => ['reference' => 'SUP-REPLY123', 'body' => 'We are looking into this.', 'author' => ['name' => 'Support Agent']]];

        $this->withHeaders($headers)->postJson('/api/v1/church/events', $ticketEvent)->assertCreated();
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $replyEvent)->assertCreated();
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $replyEvent)->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('support_replies', 1);
        $this->assertDatabaseHas('support_replies', ['body' => 'We are looking into this.']);
    }
}
