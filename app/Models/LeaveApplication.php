<?php

namespace App\Models;

use App\Models\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveApplication extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'leave_id',
        'location_id',
        'employee_id',
        'leave_type',
        'from_date',
        'to_date',
        'number_of_days',
        'is_half_day',
        'half_day_session',
        'reason',
        'contact_during_leave',
        'status',
        'approved_by',
        'approval_comments',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'cancelled_at',
    ];

    protected $casts = [
        'from_date'      => 'date',
        'to_date'        => 'date',
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
        'cancelled_at'   => 'datetime',
        'is_half_day'    => 'boolean',
        'number_of_days' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope(new LocationScope());

        static::creating(function (self $model) {
            if (empty($model->leave_id)) {
                $model->leave_id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'approved_by', 'rejected_by', 'approval_comments'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('leave');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isPending(): bool   { return $this->status === 'pending_approval'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function canBeApproved(): bool  { return $this->status === 'pending_approval'; }
    public function canBeRejected(): bool  { return $this->status === 'pending_approval'; }
    public function canBeCancelled(): bool { return in_array($this->status, ['draft', 'pending_approval']); }
}
