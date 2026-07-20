<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\LocationScope;

class StatutoryRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'location_id',
        'payroll_record_id',
        'statutory_type',
        'employee_contribution',
        'employer_contribution',
        'total_contribution',
        'details',
    ];

    protected $casts = [
        'employee_contribution' => 'decimal:2',
        'employer_contribution' => 'decimal:2',
        'total_contribution' => 'decimal:2',
        'details' => 'json',
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

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }
}
