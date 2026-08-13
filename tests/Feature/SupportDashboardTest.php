<?php

namespace Tests\Feature;

use App\Models\Installation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        foreach (['/support/tickets', '/support/community', '/support/knowledge', '/support/live', '/support/central-connection'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_guests_cannot_view_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
