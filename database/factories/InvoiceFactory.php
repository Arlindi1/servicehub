<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = 'INV-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT);

        return [
            'organization_id' => Organization::factory(),
            'client_id' => function (array $attributes): int {
                return Client::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'project_id' => null,
            'number' => $number,
            'status' => 'Draft',
            'issued_at' => null,
            'due_at' => fake()->optional()->dateTimeBetween('now', '+30 days')?->format('Y-m-d'),
            'subtotal' => 0,
            'total' => 0,
            'notes' => fake()->optional()->sentence(),
            'created_by_user_id' => function (array $attributes): int {
                return User::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
        ];
    }
}

