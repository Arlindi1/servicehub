<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
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
    public function view(User $user, Project $project): bool
    {
        if ($user->organization_id !== $project->organization_id) {
            return false;
        }

        if ($this->canManage($user)) {
            return true;
        }

        if (! $user->hasRole('Client')) {
            return false;
        }

        $clientId = Client::query()
            ->where('user_id', $user->id)
            ->value('id');

        return $clientId !== null && $project->client_id === $clientId;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        return $this->canManage($user) && $user->organization_id === $project->organization_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }
}
