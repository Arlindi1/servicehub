<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $description
     */
    public static function log(
        int $organizationId,
        ?User $actor,
        Model $subject,
        string $event,
        array $description = []
    ): ActivityLog {
        return ActivityLog::create([
            'organization_id' => $organizationId,
            'actor_user_id' => $actor?->id,
            'actor_type' => self::resolveActorType($actor),
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'event' => $event,
            'description' => $description,
            'created_at' => now(),
        ]);
    }

    private static function resolveActorType(?User $actor): string
    {
        if (! $actor) {
            return 'system';
        }

        return $actor->hasRole('Client') ? 'client' : 'staff';
    }
}

