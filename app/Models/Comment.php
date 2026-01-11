<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;

    public const AUTHOR_TYPES = [
        'staff',
        'client',
    ];

    protected $fillable = [
        'organization_id',
        'project_id',
        'author_user_id',
        'author_type',
        'body',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $user = Auth::user();

            if (! $user || $user->organization_id === null) {
                return;
            }

            $builder->where('organization_id', $user->organization_id);

            if ($user->hasRole('Client')) {
                $builder->whereHas('project');
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}

