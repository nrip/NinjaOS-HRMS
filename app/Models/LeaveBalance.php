<?php

namespace App\Models;

use App\Models\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'location_id',
        'leave_type',
        'year',
        'opening_balance',
        'accrued',
        'availed',
        'pending',
        'closing_balance',
        'expiry_date',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'accrued'         => 'decimal:2',
        'availed'         => 'decimal:2',
        'pending'         => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expiry_date'     => 'date',
        'year'            => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope(new LocationScope());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Balance mutation helpers — always call these instead of direct assignment
    // to keep the closing_balance in sync.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recompute and persist the closing balance.
     * closing = opening + accrued - availed - pending
     */
    public function recomputeClosing(): void
    {
        $this->closing_balance = round(
            (float) $this->opening_balance
            + (float) $this->accrued
            - (float) $this->availed
            - (float) $this->pending,
            2
        );
        $this->save();
    }

    /**
     * Deduct days into the pending bucket (on leave application submission).
     */
    public function deductPending(float $days): void
    {
        $this->pending = round((float) $this->pending + $days, 2);
        $this->recomputeClosing();
    }

    /**
     * Move days from pending to availed (on leave approval).
     */
    public function confirmAvailed(float $days): void
    {
        $this->pending = round(max(0.0, (float) $this->pending - $days), 2);
        $this->availed = round((float) $this->availed + $days, 2);
        $this->recomputeClosing();
    }

    /**
     * Restore days from pending back to available (on leave rejection/cancellation).
     */
    public function restorePending(float $days): void
    {
        $this->pending = round(max(0.0, (float) $this->pending - $days), 2);
        $this->recomputeClosing();
    }

    /**
     * Add accrued days (called by the monthly accrual job).
     */
    public function addAccrual(float $days): void
    {
        $this->accrued = round((float) $this->accrued + $days, 2);
        $this->recomputeClosing();
    }

    /**
     * Check whether this balance has expired (for Comp Off etc.).
     */
    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    /**
     * Check if the employee has sufficient available balance.
     */
    public function hasSufficientBalance(float $days): bool
    {
        return (float) $this->closing_balance >= $days;
    }
}
