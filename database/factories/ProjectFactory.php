<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'client_id' => function (array $attributes): int {
                return Client::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'created_by_user_id' => function (array $attributes): int {
                return User::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'title' => Str::of(fake()->sentence(4))->trim()->value(),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(Project::STATUSES),
            'priority' => fake()->randomElement(Project::PRIORITIES),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 months')?->format('Y-m-d'),
        ];
    }
}
