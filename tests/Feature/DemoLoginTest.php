<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_logs_in_demo_user_and_redirects_to_dashboard(): void
    {
        $demoUser = User::factory()->unverified()->owner()->create([
            'email' => 'demo@servicehub.test',
            'name' => 'Demo User',
        ]);

        $response = $this->get('/demo-login');

        $this->assertAuthenticatedAs($demoUser);
        $this->assertTrue($demoUser->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_demo_login_creates_demo_user_when_missing(): void
    {
        $response = $this->get('/demo-login');

        $demoUser = User::query()
            ->where('email', 'demo@servicehub.test')
            ->first();

        $this->assertNotNull($demoUser);
        $this->assertAuthenticatedAs($demoUser);
        $this->assertTrue($demoUser->hasVerifiedEmail());
        $this->assertTrue($demoUser->is_active);
        $this->assertNotNull($demoUser->organization_id);
        $this->assertTrue($demoUser->hasRole('Owner'));
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_demo_login_can_be_disabled_via_config(): void
    {
        config(['app.demo_login_enabled' => false]);

        User::factory()->owner()->create([
            'email' => 'demo@servicehub.test',
        ]);

        $response = $this->get('/demo-login');

        $response->assertStatus(404);
        $this->assertGuest();
    }
}
