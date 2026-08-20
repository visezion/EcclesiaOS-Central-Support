<?php

namespace Tests\Feature;

use App\Models\Installation;
use App\Models\CommunityQuestion;
use App\Models\LiveMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SupportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ecclesiaos_can_auto_enroll_with_the_shared_installation_key(): void
    {
        config(['support.enrollment_key' => 'enrollment-test-key']);
        $installationId = (string) Str::uuid();

        $response = $this->withHeaders(['X-EcclesiaOS-Enrollment-Key' => 'enrollment-test-key'])
            ->postJson('/api/v1/installations/enroll', ['installation_id' => $installationId, 'church_name' => 'Auto Church', 'callback_url' => 'https://auto.example.org', 'version' => '2.0.0']);

        $response->assertOk()->assertJsonPath('connected', true)->assertJsonPath('installation_id', $installationId)->assertJsonPath('api_token', fn ($token) => is_string($token) && str_starts_with($token, 'eco_'));
        $this->assertDatabaseHas('installations', ['installation_id' => $installationId, 'church_name' => 'Auto Church', 'enabled' => true]);
    }

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
        $created = $this->withHeaders($headers)->postJson('/api/v1/church/events', $event)->assertCreated()->assertJsonPath('duplicate', false);
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $event)->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('support_events', 1);
        $this->assertDatabaseHas('support_tickets', ['reference' => 'SUP-TEST123', 'subject' => 'Cannot send email', 'priority' => 'high']);
        $this->assertDatabaseHas('installations', ['installation_id' => 'install-test', 'version' => '1.0.31']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $created->json('ticket_id'));
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
        $replyEvent = ['event_id' => (string) Str::uuid(), 'event_type' => 'ticket.reply.created', 'payload' => ['reference' => 'SUP-REPLY123', 'reply' => ['body' => 'We are looking into this.', 'author_name' => 'Church Admin']]];

        $this->withHeaders($headers)->postJson('/api/v1/church/events', $ticketEvent)->assertCreated();
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $replyEvent)->assertCreated();
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $replyEvent)->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('support_replies', 1);
        $this->assertDatabaseHas('support_replies', ['body' => 'We are looking into this.']);
    }

    public function test_tracking_events_do_not_overwrite_ticket_content(): void
    {
        $token = 'eco_'.Str::random(56);
        $installation = Installation::query()->create(['installation_id' => 'install-tracking', 'church_name' => 'Tracking Church', 'token_hash' => hash('sha256', $token)]);
        $headers = ['Authorization' => 'Bearer '.$token, 'X-EcclesiaOS-Installation' => $installation->installation_id];
        $this->withHeaders($headers)->postJson('/api/v1/church/events', ['event_id' => (string) Str::uuid(), 'event_type' => 'ticket.created', 'payload' => ['reference' => 'SUP-TRACK123', 'subject' => 'Original subject', 'description' => 'Original body']])->assertCreated();
        $this->withHeaders($headers)->postJson('/api/v1/church/events', ['event_id' => (string) Str::uuid(), 'event_type' => 'ticket.tracking.updated', 'payload' => ['reference' => 'SUP-TRACK123', 'status' => 'in_progress', 'progress' => 45]])->assertCreated();

        $this->assertDatabaseHas('support_tickets', ['reference' => 'SUP-TRACK123', 'subject' => 'Original subject', 'body' => 'Original body', 'status' => 'in_progress', 'progress' => 45]);
    }

    public function test_ecclesiaos_support_payloads_are_normalized_for_the_client_contract(): void
    {
        $token = 'eco_'.Str::random(56);
        $installation = Installation::query()->create(['installation_id' => 'install-contract', 'church_name' => 'Contract Church', 'token_hash' => hash('sha256', $token)]);
        CommunityQuestion::query()->create(['installation_id' => $installation->installation_id, 'category' => 'how_to', 'title' => 'How do I update safely?', 'body' => 'Use the documented release workflow.', 'author' => ['display_name' => 'Admin'], 'church' => ['display_name' => 'Contract Church'], 'status' => 'published']);
        LiveMessage::query()->create(['installation_id' => $installation->installation_id, 'author' => ['display_name' => 'Agent'], 'body' => 'Welcome.', 'status' => 'open']);
        $headers = ['Authorization' => 'Bearer '.$token, 'X-EcclesiaOS-Installation' => $installation->installation_id];

        $this->withHeaders($headers)->getJson('/api/v1/knowledge/articles')->assertOk()->assertJsonPath('data.0.category_name', 'Deployments & Updates')->assertJsonPath('categories.0.slug', 'deployments-updates');
        $this->withHeaders($headers)->getJson('/api/v1/community/questions')->assertOk()->assertJsonPath('data.0.excerpt', 'Use the documented release workflow.')->assertJsonPath('data.0.church_name', 'Contract Church');
        $this->withHeaders($headers)->getJson('/api/v1/live-support')->assertOk()->assertJsonPath('messages.0.sent_at', fn ($value) => is_string($value))->assertJsonPath('agent.name', 'Central Support');
    }
}
