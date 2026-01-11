<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;

class ProjectFilePolicy
{
    private function canManage(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Staff']);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManage($user) || $user->hasRole('Client');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProjectFile $projectFile): bool
    {
        if ($user->organization_id !== $projectFile->organization_id) {
            return false;
        }

        return $user->can('view', $projectFile->project);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Project $project): bool
    {
        if ($user->organization_id !== $project->organization_id) {
            return false;
        }

        if ($this->canManage($user)) {
            return true;
        }

        return $user->hasRole('Client') && $user->can('view', $project);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProjectFile $projectFile): bool
    {
        return $this->canManage($user) && $user->organization_id === $projectFile->organization_id;
    }
}

