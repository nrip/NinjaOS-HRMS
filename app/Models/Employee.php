<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Scopes\LocationScope;
use Illuminate\Support\Str;

class Employee extends Model implements HasMedia
{
    use SoftDeletes, HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'employee_id',
        'employee_code',
        'location_id',
        'department_id',
        'designation_id',
        'reporting_manager_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'aadhaar',
        'pan',
        'bank_account',
        'bank_name',
        'ifsc_code',
        'status',
        'date_of_joining',
        'probation_end_date',
        'confirmation_date',
        'date_of_exit',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',
        'probation_end_date' => 'date',
        'confirmation_date' => 'date',
        'date_of_exit' => 'date',
    ];

    /**
     * Apply the LocationScope global scope to this model.
     * CRITICAL: This ensures all queries are filtered by location_id.
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new LocationScope());
        
        // Auto-generate UUID for employee_id if not set
        static::creating(function ($employee) {
            if (empty($employee->employee_id)) {
                $employee->employee_id = (string) Str::uuid();
            }
            
            // Ensure location_id is set during factory creation
            if (empty($employee->location_id) && app()->runningInConsole()) {
                $employee->location_id = Location::factory()->create()->id;
            }
        });
    }

    /**
     * ActivityLog Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'location_id', 'department_id', 'designation_id', 'reporting_manager_id',
                'status', 'date_of_joining', 'probation_end_date', 'confirmation_date', 'date_of_exit'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Employee has been {$eventName}");
    }

    /**
     * MediaLibrary Collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
            
        $this->addMediaCollection('certificates')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
            
        $this->addMediaCollection('identification')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Lifecycle Status Helper
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isOnProbation(): bool
    {
        return $this->status === 'probation';
    }

    public function isExited(): bool
    {
        return $this->status === 'exit';
    }

    /**
     * Relationships
     */
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

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'reporting_manager_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function lifecycleHistory(): HasMany
    {
        return $this->hasMany(EmployeeLifecycleHistory::class);
    }
}
