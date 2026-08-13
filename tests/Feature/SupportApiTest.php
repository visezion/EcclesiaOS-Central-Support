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
        $event = ['event_id' => (string) Str::uuid(), 'event_type' => 'ticket.updated', 'payload' => ['status' => 'triaged']];

        $this->withHeaders($headers)->getJson('/api/v1/installations/ping')->assertOk()->assertJsonPath('service', 'EcclesiaOS Central Support');
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $event)->assertCreated()->assertJsonPath('duplicate', false);
        $this->withHeaders($headers)->postJson('/api/v1/church/events', $event)->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('support_events', 1);
        $this->assertDatabaseHas('installations', ['installation_id' => 'install-test', 'version' => '1.0.31']);
    }

    public function test_invalid_installation_credentials_are_rejected(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer invalid',
            'X-EcclesiaOS-Installation' => 'unknown',
        ])->getJson('/api/v1/installations/ping')->assertUnauthorized();
    }
}
