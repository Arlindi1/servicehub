<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_owner_only_pages(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/app/team')->assertOk();
        $this->actingAs($owner)->get('/app/settings')->assertOk();
        $this->actingAs($owner)->get('/app/activity')->assertOk();
    }

    public function test_staff_cannot_access_owner_only_pages(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/app/team')->assertForbidden();
        $this->actingAs($staff)->get('/app/settings')->assertForbidden();
        $this->actingAs($staff)->get('/app/activity')->assertForbidden();
    }

    public function test_client_cannot_access_staff_app(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->get('/app/dashboard')->assertForbidden();
    }

    public function test_staff_cannot_access_client_portal(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/portal/dashboard')->assertForbidden();
    }

    public function test_user_without_organization_is_redirected_to_onboarding(): void
    {
        $ownerWithoutOrganization = User::factory()->withoutOrganization()->owner()->create();

        $this
            ->actingAs($ownerWithoutOrganization)
            ->get('/app/dashboard')
            ->assertRedirect(route('onboarding.organization.create', absolute: false));
    }
}

