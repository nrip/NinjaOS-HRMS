<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\LocationScope;

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

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new LocationScope());
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
}
