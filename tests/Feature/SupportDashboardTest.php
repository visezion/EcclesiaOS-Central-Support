<?php

namespace Tests\Feature;

use App\Models\Installation;
use App\Models\LiveMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SupportDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_installation_requires_first_super_admin_setup(): void
    {
        $this->get('/login')->assertRedirect('/setup');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/setup')->assertOk()->assertSee('Create Super Administrator');
        $this->post('/setup', ['name' => 'First Installer', 'email' => 'owner@example.com', 'password' => 'StrongPassword!234', 'password_confirmation' => 'StrongPassword!234'])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com', 'is_super_admin' => true]);
        $this->get('/setup')->assertRedirect('/login');
    }

    public function test_staff_can_sign_in_and_view_the_dashboard(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Password!234')]);
        Installation::query()->create(['installation_id' => 'church-a', 'church_name' => 'Example Church', 'token_hash' => hash('sha256', 'token')]);

        $this->get('/login')->assertOk()->assertSee('Welcome back');
        $this->post('/login', ['email' => $user->email, 'password' => 'Password!234'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Example Church')->assertSee('Central Support');
        foreach (['/system/update', '/support/tickets', '/support/community', '/support/knowledge', '/support/live', '/support/central-connection'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_guests_cannot_view_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_staff_can_start_a_github_update_from_the_dashboard(): void
    {
        config(['support.update_agent_url' => 'http://updater.test', 'support.update_agent_token' => 'test-token']);
        Http::fake(['http://updater.test/*' => Http::response(['message' => 'Update started.'], 202)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/system/update')->assertRedirect()->assertSessionHas('status', 'Update started.');
        Http::assertSent(fn ($request) => $request->url() === 'http://updater.test/update'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['ref'] === 'latest');
    }

    public function test_staff_can_reply_to_a_live_support_conversation(): void
    {
        $user = User::factory()->create();
        Installation::query()->create(['installation_id' => 'church-a', 'church_name' => 'Example Church', 'token_hash' => hash('sha256', 'token')]);
        LiveMessage::query()->create(['installation_id' => 'church-a', 'author' => ['display_name' => 'Church user', 'role' => 'church'], 'body' => 'We need help with our support setup.', 'status' => 'open']);

        $this->actingAs($user)->get('/support/live?installation_id=church-a')->assertOk()->assertSee('Example Church');
        $this->actingAs($user)->post('/support/live/church-a/messages', ['body' => 'I am checking this for you now.'])->assertRedirect();

        $this->assertDatabaseHas('live_messages', ['installation_id' => 'church-a', 'body' => 'I am checking this for you now.']);
        $this->assertSame('agent', data_get(LiveMessage::query()->latest('id')->firstOrFail()->author, 'role'));
    }

    public function test_ticket_updates_include_installation_identity_in_the_ecclesiaos_callback(): void
    {
        $user = User::factory()->create();
        Installation::query()->create([
            'installation_id' => 'church-a',
            'church_name' => 'Example Church',
            'callback_url' => 'https://church.example.org',
            'token_hash' => hash('sha256', 'token'),
            'token_encrypted' => encrypt('token'),
        ]);
        $ticket = SupportTicket::query()->create(['reference' => 'SUP-CALLBACK1', 'installation_id' => 'church-a', 'subject' => 'Callback test', 'body' => 'Test callback delivery.', 'status' => 'new', 'priority' => 'normal']);
        Http::fake(['https://church.example.org/*' => Http::response(['received' => true])]);

        $this->actingAs($user)->patch(route('support.tickets.update', $ticket), [
            'subject' => 'Callback test',
            'body' => 'Updated callback delivery.',
            'status' => 'in_progress',
            'priority' => 'high',
        ])->assertRedirect();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://church.example.org/support/central-events'
            && $request->hasHeader('Authorization', 'Bearer token')
            && $request->hasHeader('X-EcclesiaOS-Installation', 'church-a'));
    }

    public function test_default_update_knowledge_article_is_seeded_and_published(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);

        $this->assertDatabaseHas('knowledge_articles', [
            'slug' => 'updating-ecclesiaos-safely-with-central-support',
            'category' => 'Deployments & Updates',
            'published' => true,
        ]);
    }
}
