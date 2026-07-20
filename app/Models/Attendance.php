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
        'status',
        'mode',
        'latitude',
        'longitude',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'punch_in' => 'time',
        'punch_out' => 'time',
        'hours_worked' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
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
}
