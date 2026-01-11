<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_view_and_post_comments_on_their_own_project_only(): void
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
            'email' => $clientUserA->email,
        ]);

        $clientB = Client::factory()->create([
            'organization_id' => $owner->organization_id,
            'user_id' => $clientUserB->id,
            'email' => $clientUserB->email,
        ]);

        $projectA = Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $clientA->id,
            'created_by_user_id' => $owner->id,
        ]);

        $projectB = Project::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $clientB->id,
            'created_by_user_id' => $owner->id,
        ]);

        Comment::create([
            'organization_id' => $owner->organization_id,
            'project_id' => $projectA->id,
            'author_user_id' => $owner->id,
            'author_type' => 'staff',
            'body' => 'Hello from staff.',
        ]);

        $this
            ->actingAs($clientUserA)
            ->get(route('portal.projects.show', ['project' => $projectA->id, 'tab' => 'discussion'], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Projects/Show')
                ->where('tab', 'discussion')
                ->has('comments.data', 1)
            );

        $this
            ->actingAs($clientUserA)
            ->get(route('portal.projects.show', ['project' => $projectB->id, 'tab' => 'discussion'], absolute: false))
            ->assertNotFound();

        $from = route('portal.projects.show', ['project' => $projectA->id, 'tab' => 'discussion'], absolute: false);

        $this
            ->actingAs($clientUserA)
            ->from($from)
            ->post(route('portal.projects.comments.store', $projectA->id, absolute: false), [
                'body' => 'Client message.',
            ])
            ->assertRedirect($from);

        $this->assertDatabaseHas('comments', [
            'organization_id' => $owner->organization_id,
            'project_id' => $projectA->id,
            'author_user_id' => $clientUserA->id,
            'author_type' => 'client',
            'body' => 'Client message.',
        ]);

        $this
            ->actingAs($clientUserA)
            ->post(route('portal.projects.comments.store', $projectB->id, absolute: false), [
                'body' => 'Trying to post to another client project.',
            ])
            ->assertNotFound();
    }

    public function test_staff_cannot_post_comments_to_projects_outside_their_organization(): void
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
            ->post(route('app.projects.comments.store', $projectB->id, absolute: false), [
                'body' => 'Hello',
            ])
            ->assertNotFound();
    }

    public function test_creating_a_comment_requires_body_validation(): void
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

        $from = route('app.projects.show', ['project' => $project->id, 'tab' => 'discussion'], absolute: false);

        $this
            ->actingAs($owner)
            ->from($from)
            ->post(route('app.projects.comments.store', $project->id, absolute: false), [
                'body' => '   ',
            ])
            ->assertRedirect($from)
            ->assertSessionHasErrors('body');
    }

    public function test_owner_can_delete_comments(): void
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

        $comment = Comment::create([
            'organization_id' => $owner->organization_id,
            'project_id' => $project->id,
            'author_user_id' => $owner->id,
            'author_type' => 'staff',
            'body' => 'To be deleted.',
        ]);

        $from = route('app.projects.show', ['project' => $project->id, 'tab' => 'discussion'], absolute: false);

        $this
            ->actingAs($owner)
            ->from($from)
            ->delete(route('app.comments.destroy', $comment->id, absolute: false))
            ->assertRedirect($from);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }
}

