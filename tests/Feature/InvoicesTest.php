<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_only_view_their_invoices_in_the_portal(): void
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

        $invoiceA = Invoice::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $clientA->id,
            'created_by_user_id' => $owner->id,
            'number' => 'INV-00001',
            'status' => 'Sent',
            'subtotal' => 1_200,
            'total' => 1_200,
        ]);

        $invoiceB = Invoice::factory()->create([
            'organization_id' => $owner->organization_id,
            'client_id' => $clientB->id,
            'created_by_user_id' => $owner->id,
            'number' => 'INV-00002',
            'status' => 'Sent',
            'subtotal' => 900,
            'total' => 900,
        ]);

        $this
            ->actingAs($clientUserA)
            ->get('/portal/invoices')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Invoices/Index')
                ->has('invoices.data', 1)
                ->where('invoices.data.0.id', $invoiceA->id)
            );

        $this
            ->actingAs($clientUserA)
            ->get(route('portal.invoices.show', $invoiceB->id, absolute: false))
            ->assertNotFound();

        $this
            ->actingAs($clientUserA)
            ->get(route('invoices.pdf', $invoiceB->id, absolute: false))
            ->assertNotFound();
    }

    public function test_invoice_totals_are_computed_server_side(): void
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

        $response = $this->actingAs($owner)->post('/app/invoices', [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'due_at' => now()->addDays(10)->toDateString(),
            'notes' => 'Test invoice',
            'subtotal' => 1,
            'total' => 1,
            'items' => [
                [
                    'description' => 'Line A',
                    'quantity' => 2,
                    'unit_price' => 500,
                    'line_total' => 1,
                ],
                [
                    'description' => 'Line B',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'line_total' => 999_999,
                ],
            ],
        ]);

        $response->assertRedirect();

        $invoice = Invoice::withoutGlobalScopes()
            ->where('organization_id', $owner->organization_id)
            ->orderByDesc('id')
            ->firstOrFail();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'organization_id' => $owner->organization_id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'subtotal' => 1_200,
            'total' => 1_200,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Line A',
            'quantity' => 2,
            'unit_price' => 500,
            'line_total' => 1_000,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Line B',
            'quantity' => 1,
            'unit_price' => 200,
            'line_total' => 200,
        ]);
    }

    public function test_invoice_number_is_unique_per_organization(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();

        $clientA = Client::factory()->create([
            'organization_id' => $ownerA->organization_id,
        ]);

        $clientB = Client::factory()->create([
            'organization_id' => $ownerB->organization_id,
        ]);

        Invoice::withoutGlobalScopes()->create([
            'organization_id' => $ownerA->organization_id,
            'client_id' => $clientA->id,
            'project_id' => null,
            'number' => 'INV-00001',
            'status' => 'Draft',
            'issued_at' => null,
            'due_at' => null,
            'subtotal' => 0,
            'total' => 0,
            'notes' => null,
            'created_by_user_id' => $ownerA->id,
        ]);

        Invoice::withoutGlobalScopes()->create([
            'organization_id' => $ownerB->organization_id,
            'client_id' => $clientB->id,
            'project_id' => null,
            'number' => 'INV-00001',
            'status' => 'Draft',
            'issued_at' => null,
            'due_at' => null,
            'subtotal' => 0,
            'total' => 0,
            'notes' => null,
            'created_by_user_id' => $ownerB->id,
        ]);

        $this->expectException(QueryException::class);

        Invoice::withoutGlobalScopes()->create([
            'organization_id' => $ownerA->organization_id,
            'client_id' => $clientA->id,
            'project_id' => null,
            'number' => 'INV-00001',
            'status' => 'Draft',
            'issued_at' => null,
            'due_at' => null,
            'subtotal' => 0,
            'total' => 0,
            'notes' => null,
            'created_by_user_id' => $ownerA->id,
        ]);
    }
}

