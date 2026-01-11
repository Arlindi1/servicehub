<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_organization_settings(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->post('/app/settings', [
            'name' => 'Acme Studio Updated',
            'brand_color' => '#0ea5e9',
            'invoice_prefix' => 'ACME',
            'invoice_due_days_default' => 30,
            'billing_email' => 'billing@acme.test',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('organizations', [
            'id' => $owner->organization_id,
            'name' => 'Acme Studio Updated',
            'brand_color' => '#0ea5e9',
            'invoice_prefix' => 'ACME',
            'invoice_due_days_default' => 30,
            'billing_email' => 'billing@acme.test',
        ]);
    }

    public function test_staff_and_client_cannot_update_settings(): void
    {
        $staff = User::factory()->staff()->create();
        $client = User::factory()->client()->create();

        $this->actingAs($staff)->post('/app/settings', [
            'name' => 'Nope',
            'invoice_prefix' => 'INV',
            'invoice_due_days_default' => 14,
        ])->assertForbidden();

        $this->actingAs($client)->post('/app/settings', [
            'name' => 'Nope',
            'invoice_prefix' => 'INV',
            'invoice_due_days_default' => 14,
        ])->assertForbidden();
    }

    public function test_invoice_prefix_and_default_due_days_are_used_when_creating_invoices(): void
    {
        $now = now();
        $this->travelTo($now);

        $owner = User::factory()->owner()->create();
        $owner->organization->update([
            'invoice_prefix' => 'ACME',
            'invoice_due_days_default' => 30,
        ]);

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
            'notes' => 'Test invoice',
            'items' => [
                [
                    'description' => 'Line A',
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ]);

        $response->assertRedirect();

        $invoice = Invoice::withoutGlobalScopes()
            ->where('organization_id', $owner->organization_id)
            ->orderByDesc('id')
            ->firstOrFail();

        $this->assertStringStartsWith('ACME-', $invoice->number);
        $this->assertSame($now->copy()->addDays(30)->toDateString(), $invoice->due_at?->toDateString());
    }
}

