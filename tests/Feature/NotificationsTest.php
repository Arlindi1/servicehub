<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ClientUploadedFileNotification;
use App\Notifications\ProjectCommentedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_created_for_client_comment_on_project(): void
    {
        $owner = User::factory()->owner()->create();

        $staff = User::factory()->staff()->create([
            'organization_id' => $owner->organization_id,
        ]);

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

        $project->staff()->syncWithPivotValues([$staff->id], [
            'organization_id' => $owner->organization_id,
        ]);

        $from = route('portal.projects.show', ['project' => $project->id, 'tab' => 'discussion'], absolute: false);

        $this
            ->actingAs($clientUser)
            ->from($from)
            ->post(route('portal.projects.comments.store', $project->id, absolute: false), [
                'body' => 'Client comment for notifications test.',
            ])
            ->assertRedirect($from);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'notifiable_type' => User::class,
            'type' => ProjectCommentedNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $staff->id,
            'notifiable_type' => User::class,
            'type' => ProjectCommentedNotification::class,
        ]);
    }

    public function test_notifications_are_created_for_client_file_upload(): void
    {
        Storage::fake('local');

        $owner = User::factory()->owner()->create();

        $staff = User::factory()->staff()->create([
            'organization_id' => $owner->organization_id,
        ]);

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

        $project->staff()->syncWithPivotValues([$staff->id], [
            'organization_id' => $owner->organization_id,
        ]);

        $from = route('portal.projects.show', ['project' => $project->id, 'tab' => 'files'], absolute: false);

        $this
            ->actingAs($clientUser)
            ->from($from)
            ->post(route('portal.projects.files.store', $project->id, absolute: false), [
                'file_type' => 'Client Upload',
                'file' => UploadedFile::fake()->create('brief.zip', 10, 'application/zip'),
            ])
            ->assertRedirect($from);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'notifiable_type' => User::class,
            'type' => ClientUploadedFileNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $staff->id,
            'notifiable_type' => User::class,
            'type' => ClientUploadedFileNotification::class,
        ]);
    }

    public function test_client_cannot_access_app_notification_routes(): void
    {
        $clientUser = User::factory()->client()->create();

        $this
            ->actingAs($clientUser)
            ->patch(route('app.notifications.readAll', absolute: false))
            ->assertForbidden();
    }
}

