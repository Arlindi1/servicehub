<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_clients_scoped_to_organization_with_search(): void
    {
        $owner = User::factory()->owner()->create();

        $match = Client::factory()->create([
            'organization_id' => $owner->organization_id,
            'name' => 'Alpha Studio',
            'email' => 'alpha@example.com',
        ]);

        Client::factory()->create([
            'organization_id' => $owner->organization_id,
            'name' => 'Beta Studio',
            'email' => 'beta@example.com',
        ]);

        Client::factory()->create([
            'name' => 'Other Org Client',
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($owner)->get('/app/clients?search=alpha');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Clients/Index')
                ->where('filters.search', 'alpha')
                ->has('clients.data', 1)
                ->where('clients.data.0.id', $match->id)
            );
    }

    public function test_staff_can_create_a_client_in_their_organization(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)->post('/app/clients', [
            'name' => 'New Client',
            'email' => 'new-client@example.com',
            'phone' => '555-010-9999',
            'notes' => 'Test notes',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'organization_id' => $staff->organization_id,
            'name' => 'New Client',
            'email' => 'new-client@example.com',
        ]);
    }

    public function test_client_role_cannot_access_app_clients_routes(): void
    {
        $clientUser = User::factory()->client()->create();

        $this->actingAs($clientUser)->get('/app/clients')->assertForbidden();
    }

    public function test_users_cannot_view_clients_from_other_organizations(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();

        $clientB = Client::factory()->create([
            'organization_id' => $ownerB->organization_id,
        ]);

        $this
            ->actingAs($ownerA)
            ->get(route('app.clients.show', $clientB->id, absolute: false))
            ->assertNotFound();
    }

    public function test_client_show_includes_latest_projects_and_invoices_for_the_client(): void
    {
        $owner = User::factory()->owner()->create();

        $client = Client::factory()->create([
            'organization_id' => $owner->organization_id,
            'name' => 'Zenith Co',
            'email' => 'zenith@example.com',
        ]);

        $projects = Project::factory()
            ->count(6)
            ->sequence(fn ($sequence) => [
                'title' => 'Project '.($sequence->index + 1),
            ])
            ->create([
                'organization_id' => $owner->organization_id,
                'client_id' => $client->id,
                'created_by_user_id' => $owner->id,
            ]);

        $invoices = Invoice::factory()
            ->count(6)
            ->sequence(fn ($sequence) => [
                'number' => 'INV-'.str_pad((string) ($sequence->index + 1), 5, '0', STR_PAD_LEFT),
                'status' => 'Sent',
                'subtotal' => 1_000,
                'total' => 1_000,
            ])
            ->create([
                'organization_id' => $owner->organization_id,
                'client_id' => $client->id,
                'created_by_user_id' => $owner->id,
            ]);

        $response = $this->actingAs($owner)->get(route('app.clients.show', $client->id, absolute: false));

        $latestProject = $projects->sortByDesc('id')->first();
        $latestInvoice = $invoices->sortByDesc('id')->first();

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Clients/Show')
                ->where('client.id', $client->id)
                ->where('projectsCount', 6)
                ->where('invoicesCount', 6)
                ->has('latestProjects', 5)
                ->has('latestInvoices', 5)
                ->where('latestProjects.0.id', $latestProject->id)
                ->where('latestInvoices.0.id', $latestInvoice->id)
            );
    }
}
