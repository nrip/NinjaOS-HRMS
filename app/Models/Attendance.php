<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\LocationScope;

class Attendance extends Model
{
    use SoftDeletes;

    protected $table = 'attendance';

    protected $fillable = [
        'location_id',
        'employee_id',
        'attendance_date',
        'punch_in',
        'punch_out',
        'hours_worked',
        'ot_hours',
        'status',
        'mode',
        'punch_source',
        'device_id',
        'latitude',
        'longitude',
        'geo_distance_meters',
        'shift_id',
        'regularization_status',
        'regularization_reason',
        'regularized_by',
        'regularized_at',
        'notes',
    ];

    protected $casts = [
        // Use 'string' for attendance_date to avoid Carbon datetime mismatch
        // in firstOrNew() queries — the unique constraint needs exact Y-m-d matching.
        'attendance_date'       => 'string',
        'punch_in'              => 'datetime',
        'punch_out'             => 'datetime',
        'hours_worked'          => 'decimal:2',
        'ot_hours'              => 'decimal:2',
        'geo_distance_meters'   => 'decimal:2',
        'latitude'              => 'decimal:8',
        'longitude'             => 'decimal:8',
        'regularized_at'        => 'datetime',
    ];

    /**
     * Apply the LocationScope global scope to this model.
     */
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

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function regularizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regularized_by');
    }

    /**
     * Check if this attendance record has overtime.
     */
    public function hasOT(): bool
    {
        return ($this->ot_hours ?? 0) > 0;
    }

    /**
     * Check if the employee was late based on status.
     */
    public function isLate(): bool
    {
        return in_array($this->status, ['late', 'late_half_day']);
    }
}
