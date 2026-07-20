<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\LocationScope;

class PayrollRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_id',
        'location_id',
        'employee_id',
        'payroll_year',
        'payroll_month',
        'basic_salary',
        'gross_salary',
        'net_salary',
        'pf_deduction',
        'esi_deduction',
        'pt_deduction',
        'tds_deduction',
        'other_deductions',
        'other_earnings',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'pf_deduction' => 'decimal:2',
        'esi_deduction' => 'decimal:2',
        'pt_deduction' => 'decimal:2',
        'tds_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'other_earnings' => 'decimal:2',
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

    public function statutoryRecords(): HasMany
    {
        return $this->hasMany(StatutoryRecord::class);
    }
}
