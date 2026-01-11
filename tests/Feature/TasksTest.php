<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_task_for_a_project_in_their_organization(): void
    {
        $owner = User::factory()->owner()->create();

        $client = Client::factory()->create([
            'organization_id' => $owner->organization_id,
        ]);

        $project = Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $client->id,
            'created_by_user_id' => $owner->id,
        ]);

        $staff = User::factory()->staff()->create([
            'organization_id' => $owner->organization_id,
        ]);

        $from = route('app.projects.show', ['project' => $project->id, 'tab' => 'tasks'], absolute: false);

        $response = $this
            ->actingAs($owner)
            ->from($from)
            ->post(route('app.projects.tasks.store', $project->id, absolute: false), [
                'title' => 'Write project brief',
                'status' => 'Todo',
                'assigned_to_user_id' => $staff->id,
                'due_date' => now()->addDays(3)->toDateString(),
            ]);

        $response->assertRedirect($from);

        $this->assertDatabaseHas('tasks', [
            'organization_id' => $owner->organization_id,
            'project_id' => $project->id,
            'title' => 'Write project brief',
            'status' => 'Todo',
            'assigned_to_user_id' => $staff->id,
            'created_by_user_id' => $owner->id,
        ]);
    }

    public function test_staff_can_quick_update_task_status(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->staff()->create([
            'organization_id' => $owner->organization_id,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $owner->organization_id,
        ]);

        $project = Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $client->id,
            'created_by_user_id' => $owner->id,
        ]);

        $task = Task::factory()->create([
            'organization_id' => $owner->organization_id,
            'project_id' => $project->id,
            'title' => 'Collect client feedback',
            'status' => 'Todo',
            'created_by_user_id' => $owner->id,
        ]);

        $from = route('app.projects.show', ['project' => $project->id, 'tab' => 'tasks'], absolute: false);

        $response = $this
            ->actingAs($staff)
            ->from($from)
            ->patch(route('app.tasks.status.update', $task->id, absolute: false), [
                'status' => 'Done',
            ]);

        $response->assertRedirect($from);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'Done',
        ]);
    }

    public function test_client_can_view_tasks_for_their_own_project_but_cannot_create_tasks(): void
    {
        $owner = User::factory()->owner()->create();

        $clientUser = User::factory()->client()->create([
            'organization_id' => $owner->organization_id,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $owner->organization_id,
            'user_id' => $clientUser->id,
            'email' => $clientUser->email,
        ]);

        $project = Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $client->id,
            'created_by_user_id' => $owner->id,
        ]);

        Task::factory()->count(2)->create([
            'organization_id' => $owner->organization_id,
            'project_id' => $project->id,
            'created_by_user_id' => $owner->id,
        ]);

        $this
            ->actingAs($clientUser)
            ->get(route('portal.projects.show', ['project' => $project->id, 'tab' => 'tasks'], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Projects/Show')
                ->where('tab', 'tasks')
                ->has('tasks.data', 2)
            );

        $this
            ->actingAs($clientUser)
            ->post(route('app.projects.tasks.store', $project->id, absolute: false), [
                'title' => 'Client tries to create task',
                'status' => 'Todo',
            ])
            ->assertForbidden();
    }

    public function test_users_cannot_modify_tasks_from_other_organizations(): void
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

        $taskB = Task::factory()->create([
            'organization_id' => $ownerB->organization_id,
            'project_id' => $projectB->id,
            'created_by_user_id' => $ownerB->id,
        ]);

        $this
            ->actingAs($ownerA)
            ->patch(route('app.tasks.status.update', $taskB->id, absolute: false), [
                'status' => 'Done',
            ])
            ->assertNotFound();
    }
}

