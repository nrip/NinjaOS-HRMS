<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\LocationScope;

class LeaveApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'leave_id',
        'location_id',
        'employee_id',
        'leave_type',
        'from_date',
        'to_date',
        'number_of_days',
        'reason',
        'contact_during_leave',
        'status',
        'approved_by',
        'approval_comments',
        'approved_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new LocationScope());
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
}
