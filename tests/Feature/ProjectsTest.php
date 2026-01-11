<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_projects_scoped_to_organization_with_search_and_filters(): void
    {
        $owner = User::factory()->owner()->create();

        $client = Client::factory()->create([
            'organization_id' => $owner->organization_id,
            'name' => 'Zenith Co',
        ]);

        $match = Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $client->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Zenith Website',
            'status' => 'Active',
            'priority' => 'medium',
        ]);

        Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $client->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Zenith Draft',
            'status' => 'Draft',
            'priority' => 'low',
        ]);

        $otherOrgOwner = User::factory()->owner()->create();
        $otherOrgClient = Client::factory()->create([
            'organization_id' => $otherOrgOwner->organization_id,
        ]);

        Project::factory()->create([
            'organization_id' => $otherOrgOwner->organization_id,
            'client_id' => $otherOrgClient->id,
            'created_by_user_id' => $otherOrgOwner->id,
            'title' => 'Zenith Website',
            'status' => 'Active',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->get('/app/projects?search=zenith&status=Active');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Projects/Index')
                ->where('filters.search', 'zenith')
                ->where('filters.status', 'Active')
                ->has('projects.data', 1)
                ->where('projects.data.0.id', $match->id)
            );
    }

    public function test_owner_can_create_a_project_with_staff_assignments(): void
    {
        $owner = User::factory()->owner()->create();

        $staff = User::factory()->staff()->create([
            'organization_id' => $owner->organization_id,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $owner->organization_id,
        ]);

        $response = $this->actingAs($owner)->post('/app/projects', [
            'title' => 'New Project',
            'description' => 'Test description',
            'status' => 'Active',
            'priority' => 'high',
            'due_date' => now()->addWeek()->toDateString(),
            'client_id' => $client->id,
            'staff_ids' => [$staff->id],
        ]);

        $response->assertRedirect();

        $project = Project::withoutGlobalScopes()
            ->where('title', 'New Project')
            ->firstOrFail();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'organization_id' => $owner->organization_id,
            'client_id' => $client->id,
            'created_by_user_id' => $owner->id,
            'status' => 'Active',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('project_user', [
            'organization_id' => $owner->organization_id,
            'project_id' => $project->id,
            'user_id' => $staff->id,
        ]);
    }

    public function test_client_can_only_view_projects_linked_to_their_client_record(): void
    {
        $owner = User::factory()->owner()->create();

        $clientUserA = User::factory()->client()->create([
            'organization_id' => $owner->organization_id,
        ]);
        $clientUserB = User::factory()->client()->create([
            'organization_id' => $owner->organization_id,
        ]);

        $clientA = Client::factory()->create([
            'organization_id' => $owner->organization_id,
            'user_id' => $clientUserA->id,
            'name' => 'Client A',
            'email' => $clientUserA->email,
        ]);

        $clientB = Client::factory()->create([
            'organization_id' => $owner->organization_id,
            'user_id' => $clientUserB->id,
            'name' => 'Client B',
            'email' => $clientUserB->email,
        ]);

        $projectA = Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $clientA->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Project A',
        ]);

        $projectB = Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $clientB->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Project B',
        ]);

        $this
            ->actingAs($clientUserA)
            ->get('/portal/projects')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Projects/Index')
                ->has('projects.data', 1)
                ->where('projects.data.0.id', $projectA->id)
            );

        $this
            ->actingAs($clientUserA)
            ->get(route('portal.projects.show', $projectB->id, absolute: false))
            ->assertNotFound();
    }

    public function test_users_cannot_view_projects_from_other_organizations(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();

        $clientB = Client::factory()->create([
            'organization_id' => $ownerB->organization_id,
        ]);

        $projectB = Project::factory()->create([
            'organization_id' => $ownerB->organization_id,
            'client_id' => $clientB->id,
            'created_by_user_id' => $ownerB->id,
        ]);

        $this
            ->actingAs($ownerA)
            ->get(route('app.projects.show', $projectB->id, absolute: false))
            ->assertNotFound();
    }

    public function test_client_role_cannot_access_app_projects_routes(): void
    {
        $clientUser = User::factory()->client()->create();

        $this->actingAs($clientUser)->get('/app/projects')->assertForbidden();
    }
}

