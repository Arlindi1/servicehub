<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    protected $model = ProjectFile::class;

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
            'uploaded_by_user_id' => function (array $attributes): int {
                return User::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'uploader_type' => fake()->randomElement(ProjectFile::UPLOADER_TYPES),
            'file_type' => fake()->randomElement(ProjectFile::FILE_TYPES),
            'original_name' => Str::of(fake()->words(3, true))->append('.pdf')->value(),
            'path' => 'project-files/demo/'.Str::uuid()->toString().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
        ];
    }
}

