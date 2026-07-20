<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'state_code',
        'pin_code',
        'gis_lat',
        'gis_lng',
        'attendance_radius_meters',
        'is_active',
    ];

    protected $casts = [
        'is_active'                 => 'boolean',
        'gis_lat'                   => 'decimal:8',
        'gis_lng'                   => 'decimal:8',
        'attendance_radius_meters'  => 'integer',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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

    public function holidayCalendars(): HasMany
    {
        return $this->hasMany(HolidayCalendar::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function jobRequisitions(): HasMany
    {
        return $this->hasMany(JobRequisition::class);
    }
}
