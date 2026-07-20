<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLineItem extends Model
{
    protected $fillable = [
        'payroll_record_id', 'type', 'code', 'label', 'amount', 'is_statutory',
    ];

    protected $casts = [
        'amount'       => 'float',
        'is_statutory' => 'boolean',
    ];

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }
}
