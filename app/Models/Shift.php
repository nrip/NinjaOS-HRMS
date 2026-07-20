<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\LocationScope;

class Shift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'location_id',
        'name',
        'code',
        'type',
        'start_time',
        'end_time',
        'duration_hours',
        'working_days',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time',
        'is_active' => 'boolean',
        'working_days' => 'json',
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
}
