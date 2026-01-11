<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Owner');
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $user->hasRole('Owner')
            && $activityLog->organization_id === $user->organization_id;
    }
}

