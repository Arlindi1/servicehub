<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Owner') && $user->organization_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Owner') && $user->organization_id !== null;
    }

    public function update(User $user, User $member): bool
    {
        if (! $user->hasRole('Owner') || $user->organization_id === null) {
            return false;
        }

        if ($member->organization_id !== $user->organization_id) {
            return false;
        }

        if ($member->hasRole('Owner')) {
            return false;
        }

        return true;
    }
}

