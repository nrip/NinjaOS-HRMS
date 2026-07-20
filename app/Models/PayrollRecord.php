<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Scopes\LocationScope;

class PayrollRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_id', 'employee_id', 'employee_code', 'location_id', 'state_code',
        'payroll_month', 'payroll_year', 'tax_regime',
        'gross_salary', 'basic_salary', 'hra', 'special_allowance',
        'ot_earnings', 'encashment_payout',
        'lwp_days', 'lwp_deduction', 'effective_gross',
        'employee_pf', 'employer_pf', 'employee_esi', 'employer_esi',
        'professional_tax', 'monthly_tds', 'notice_pay_recovery',
        'total_deductions', 'net_pay',
        'prev_net_pay', 'variance_percent', 'variance_flag',
        'variance_acknowledged', 'variance_acknowledged_by', 'variance_acknowledged_at',
        'payslip_snapshot',
        'legacy_net_pay', 'reconciliation_variance', 'reconciliation_cleared',
        'status', 'approved_by', 'approved_at', 'finalized_by', 'finalized_at',
        'rejection_reason',
    ];

    protected $casts = [
        'payslip_snapshot'        => 'array',
        'variance_flag'           => 'boolean',
        'variance_acknowledged'   => 'boolean',
        'reconciliation_cleared'  => 'boolean',
        'approved_at'             => 'datetime',
        'finalized_at'            => 'datetime',
        'variance_acknowledged_at'=> 'datetime',
        'lwp_days'                => 'float',
        'gross_salary'            => 'float',
        'net_pay'                 => 'float',
        'prev_net_pay'            => 'float',
        'variance_percent'        => 'float',
    ];

    protected static function boot(): void
    {
        parent::boot();
        // Apply LocationScope so Location HR can only see their location's payroll records.
        static::addGlobalScope(new LocationScope());
        static::creating(function (self $model) {
            if (empty($model->payroll_id)) {
                $model->payroll_id = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(PayrollLineItem::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    // ── State machine helpers ─────────────────────────────────────────────────

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isFinalized(): bool  { return $this->status === 'finalized'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }

    public function approve(int $userId): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function finalize(int $userId): void
    {
        $this->update([
            'status'       => 'finalized',
            'finalized_by' => $userId,
            'finalized_at' => now(),
        ]);
    }

    public function reject(int $userId, string $reason): void
    {
        $this->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function acknowledgeVariance(int $userId): void
    {
        $this->update([
            'variance_acknowledged'    => true,
            'variance_acknowledged_by' => $userId,
            'variance_acknowledged_at' => now(),
        ]);
    }
}
