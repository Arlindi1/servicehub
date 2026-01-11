<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_staff_member_with_a_password_setup_link(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->post('/app/team', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'set_password_later' => true,
        ]);

        $response
            ->assertRedirect(route('app.team.index', absolute: false))
            ->assertSessionHas('reset_link');

        $created = User::query()->where('email', 'newstaff@example.com')->firstOrFail();

        $this->assertSame($owner->organization_id, $created->organization_id);
        $this->assertTrue($created->hasRole('Staff'));
        $this->assertTrue($created->is_active);
    }

    public function test_staff_cannot_manage_team_routes(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/app/team')->assertForbidden();

        $this->actingAs($staff)->post('/app/team', [
            'name' => 'Blocked',
            'email' => 'blocked@example.com',
            'set_password_later' => true,
        ])->assertForbidden();
    }

    public function test_owner_can_change_a_team_members_role(): void
    {
        $owner = User::factory()->owner()->create();

        $member = User::factory()
            ->staff()
            ->create([
                'organization_id' => $owner->organization_id,
                'email' => 'member@example.com',
            ]);

        $response = $this->actingAs($owner)->patch("/app/team/{$member->id}/role", [
            'role' => 'Client',
        ]);

        $response->assertRedirect();

        $this->assertTrue($member->refresh()->hasRole('Client'));
    }

    public function test_owner_can_deactivate_and_reactivate_a_team_member(): void
    {
        $owner = User::factory()->owner()->create();

        $member = User::factory()
            ->staff()
            ->create([
                'organization_id' => $owner->organization_id,
            ]);

        $this
            ->actingAs($owner)
            ->patch("/app/team/{$member->id}/active", [
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertFalse($member->refresh()->is_active);

        $this
            ->actingAs($owner)
            ->patch("/app/team/{$member->id}/active", [
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($member->refresh()->is_active);
    }

    public function test_owner_cannot_manage_users_from_other_organizations(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();

        $memberB = User::factory()
            ->staff()
            ->create([
                'organization_id' => $ownerB->organization_id,
            ]);

        $this
            ->actingAs($ownerA)
            ->patch("/app/team/{$memberB->id}/role", [
                'role' => 'Client',
            ])
            ->assertNotFound();
    }
}

