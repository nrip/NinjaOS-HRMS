<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLifecycleHistory extends Model
{
    protected $table = 'employee_lifecycle_history';

    protected $fillable = [
        'employee_id',
        'previous_status',
        'new_status',
        'reason',
        'changed_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
