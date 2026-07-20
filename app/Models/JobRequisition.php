<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JobRequisition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'requisition_id',
        'location_id',
        'department_id',
        'designation_id',
        'number_of_positions',
        'job_description',
        'required_skills',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'posting_date',
        'closing_date',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'posting_date' => 'date',
        'closing_date' => 'date',
    ];

    public const STATUSES = [
        'draft',
        'pending_location_hr',
        'pending_central_hr',
        'open',
        'closed',
        'cancelled',
        'rejected',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new LocationScope());

        static::creating(function (self $model): void {
            if (empty($model->requisition_id)) {
                $model->requisition_id = (string) Str::uuid();
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'requisition_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isPendingApproval(): bool
    {
        return in_array($this->status, ['pending_location_hr', 'pending_central_hr'], true);
    }
}
