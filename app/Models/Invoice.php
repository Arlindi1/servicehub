<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    public const STATUSES = [
        'Draft',
        'Sent',
        'Paid',
        'Overdue',
        'Void',
    ];

    protected $fillable = [
        'organization_id',
        'client_id',
        'project_id',
        'number',
        'status',
        'issued_at',
        'due_at',
        'subtotal',
        'total',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $user = Auth::user();

            if (! $user || $user->organization_id === null) {
                return;
            }

            $builder->where('organization_id', $user->organization_id);

            if ($user->hasRole('Client')) {
                $clientId = Client::query()
                    ->where('user_id', $user->id)
                    ->value('id');

                if ($clientId) {
                    $builder->where('client_id', $clientId);
                } else {
                    $builder->whereRaw('1 = 0');
                }
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}

