<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_upload_non_client_upload_file_types(): void
    {
        Storage::fake('local');

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

        $from = route('portal.projects.show', ['project' => $project->id, 'tab' => 'files'], absolute: false);

        $this
            ->actingAs($clientUser)
            ->from($from)
            ->post(route('portal.projects.files.store', $project->id, absolute: false), [
                'file_type' => 'Deliverable',
                'file' => UploadedFile::fake()->create('deliverable.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect($from)
            ->assertSessionHasErrors('file_type');

        $this
            ->actingAs($clientUser)
            ->from($from)
            ->post(route('portal.projects.files.store', $project->id, absolute: false), [
                'file_type' => 'Client Upload',
                'file' => UploadedFile::fake()->create('brief.zip', 10, 'application/zip'),
            ])
            ->assertRedirect($from)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_files', [
            'organization_id' => $owner->organization_id,
            'project_id' => $project->id,
            'uploaded_by_user_id' => $clientUser->id,
            'uploader_type' => 'client',
            'file_type' => 'Client Upload',
            'original_name' => 'brief.zip',
        ]);
    }

    public function test_client_cannot_access_files_from_other_clients_projects(): void
    {
        Storage::fake('local');

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

        $this
            ->actingAs($owner)
            ->post(route('app.projects.files.store', $projectA->id, absolute: false), [
                'file_type' => 'Deliverable',
                'file' => UploadedFile::fake()->create('deliverable.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = ProjectFile::withoutGlobalScopes()
            ->where('project_id', $projectA->id)
            ->firstOrFail();

        $this
            ->actingAs($clientUserA)
            ->get(route('files.download', $file->id, absolute: false))
            ->assertOk()
            ->assertHeaderContains('content-disposition', 'deliverable.pdf');

        $this
            ->actingAs($clientUserB)
            ->get(route('files.download', $file->id, absolute: false))
            ->assertNotFound();

        $this
            ->actingAs($clientUserA)
            ->post(route('portal.projects.files.store', $projectB->id, absolute: false), [
                'file_type' => 'Client Upload',
                'file' => UploadedFile::fake()->create('brief.zip', 10, 'application/zip'),
            ])
            ->assertNotFound();
    }

    public function test_download_route_requires_authorization(): void
    {
        Storage::fake('local');

        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();

        $clientA = Client::factory()->create([
            'organization_id' => $ownerA->organization_id,
        ]);

        $projectA = Project::factory()->create([
            'organization_id' => $ownerA->organization_id,
            'client_id' => $clientA->id,
            'created_by_user_id' => $ownerA->id,
        ]);

        $this
            ->actingAs($ownerA)
            ->post(route('app.projects.files.store', $projectA->id, absolute: false), [
                'file_type' => 'Contract',
                'file' => UploadedFile::fake()->create('contract.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = ProjectFile::withoutGlobalScopes()
            ->where('project_id', $projectA->id)
            ->firstOrFail();

        $this
            ->actingAs($ownerB)
            ->get(route('files.download', $file->id, absolute: false))
            ->assertNotFound();
    }
}

