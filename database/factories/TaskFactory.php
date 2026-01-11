<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => function (array $attributes): int {
                return Project::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'title' => Str::of(fake()->sentence(5))->trim()->value(),
            'status' => fake()->randomElement(Task::STATUSES),
            'assigned_to_user_id' => null,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+1 month')?->format('Y-m-d'),
            'created_by_user_id' => function (array $attributes): int {
                return User::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
        ];
    }
}

