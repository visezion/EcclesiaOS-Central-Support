<?php

namespace Tests\Feature;

use App\Models\Installation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SupportDashboardTest extends TestCase
{
    use RefreshDatabase;

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
            && $request['branch'] === 'main');
    }
}
