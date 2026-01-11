<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ProjectFile extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFileFactory> */
    use HasFactory;

    public const FILE_TYPES = [
        'Deliverable',
        'Client Upload',
        'Contract',
        'Other',
    ];

    public const UPLOADER_TYPES = [
        'staff',
        'client',
    ];

    protected $fillable = [
        'organization_id',
        'project_id',
        'uploaded_by_user_id',
        'uploader_type',
        'file_type',
        'original_name',
        'path',
        'mime_type',
        'size',
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
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}

