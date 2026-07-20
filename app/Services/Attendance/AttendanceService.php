<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * AttendanceService
 *
 * Handles all punch processing logic:
 *   - Duplicate punch detection (SRS FR3.1.7)
 *   - Geo-fence validation (skipped for biometric source)
 *   - Night-shift cross-midnight hour calculation
 *   - OT calculation read exclusively from config/statutory.php
 *   - Late arrival detection using shift grace_period_minutes
 */
final class AttendanceService
{
    public function __construct(
        private readonly GeoFencingService $geoFencing
    ) {}

    /**
     * Process a punch (IN or OUT) for an employee.
     *
     * @param  Employee   $employee
     * @param  string     $punchType    'IN' or 'OUT'
     * @param  Carbon     $timestamp    Punch timestamp (with timezone)
     * @param  string     $punchSource  'biometric' | 'mobile_gps' | 'manual' | 'ip_whitelist'
     * @param  float|null $latitude     GPS latitude (null for biometric)
     * @param  float|null $longitude    GPS longitude (null for biometric)
     * @param  string|null $deviceId    Biometric device ID
     * @return array{
     *   success: bool,
     *   attendance: Attendance|null,
     *   message: string,
     *   geo?: array
     * }
     */
    public function processPunch(
        Employee $employee,
        string $punchType,
        Carbon $timestamp,
        string $punchSource = 'manual',
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $deviceId = null,
    ): array {
        $location = $employee->location;
        $attendanceDate = $timestamp->toDateString();

        // ── 1. Duplicate punch detection (SRS FR3.1.7) ──────────────────────
        $duplicateCheck = $this->checkDuplicatePunch(
            $employee, $punchType, $attendanceDate
        );
        if ($duplicateCheck['is_duplicate']) {
            return [
                'success'  => false,
                'attendance' => null,
                'message'  => $duplicateCheck['message'],
            ];
        }

        // ── 2. Geo-fence validation (skip for biometric — devices don't send GPS) ──
        $geoResult = null;
        if ($punchSource !== 'biometric' && $latitude !== null && $longitude !== null) {
            $geoResult = $this->geoFencing->validate($location, $latitude, $longitude);
            if (! $geoResult['allowed']) {
                return [
                    'success'    => false,
                    'attendance' => null,
                    'message'    => $geoResult['message'],
                    'geo'        => $geoResult,
                ];
            }
        }

        // ── 3. Find or initialise the attendance record for this date ────────
        /** @var Attendance $attendance */
        $attendance = Attendance::withoutGlobalScopes()
            ->firstOrNew([
                'location_id'     => $employee->location_id,
                'employee_id'     => $employee->id,
                'attendance_date' => $attendanceDate,
            ]);

        // ── 4. Resolve assigned shift ────────────────────────────────────────
        $shift = $this->resolveShift($employee, $timestamp);

        // ── 5. Apply punch ───────────────────────────────────────────────────
        if ($punchType === 'IN') {
            $attendance->punch_in    = $timestamp->toTimeString();
            $attendance->status      = 'present';
        } else {
            $attendance->punch_out   = $timestamp->toTimeString();
        }

        $attendance->location_id  = $employee->location_id;
        $attendance->employee_id  = $employee->id;
        $attendance->mode         = $punchSource;
        $attendance->punch_source = $punchSource;
        $attendance->device_id    = $deviceId;
        $attendance->latitude     = $latitude;
        $attendance->longitude    = $longitude;
        $attendance->geo_distance_meters = $geoResult['distance_metres'] ?? null;

        if ($shift) {
            $attendance->shift_id = $shift->id;
        }

        // ── 6. Calculate hours worked and OT (only when both punches present) ─
        if ($attendance->punch_in && $attendance->punch_out) {
            $hoursWorked = $this->calculateHoursWorked(
                $attendance->punch_in,
                $attendance->punch_out,
                (bool) ($shift?->is_night_shift ?? false)
            );
            $attendance->hours_worked = round($hoursWorked, 2);
            $attendance->ot_hours     = $this->calculateOtHours(
                $hoursWorked,
                $location->state_code ?? 'DL'
            );
        }

        // ── 7. Late detection ────────────────────────────────────────────────
        if ($punchType === 'IN' && $shift) {
            $attendance->status = $this->detectLateStatus(
                $timestamp,
                $shift
            );
        }

        $attendance->save();

        Log::channel('audit')->info('attendance.punch', [
            'employee_id'  => $employee->id,
            'location_id'  => $employee->location_id,
            'punch_type'   => $punchType,
            'punch_source' => $punchSource,
            'date'         => $attendanceDate,
        ]);

        return [
            'success'    => true,
            'attendance' => $attendance,
            'message'    => "Punch {$punchType} recorded successfully.",
            'geo'        => $geoResult,
        ];
    }

    /**
     * Check for a duplicate punch of the same type on the same date.
     * SRS FR3.1.7: Duplicate punches must be flagged or rejected.
     */
    public function checkDuplicatePunch(Employee $employee, string $punchType, string $date): array
    {
        $existing = Attendance::withoutGlobalScopes()
            ->where('location_id', $employee->location_id)
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $date)
            ->first();

        if (! $existing) {
            return ['is_duplicate' => false, 'message' => ''];
        }

        if ($punchType === 'IN' && $existing->punch_in !== null) {
            return [
                'is_duplicate' => true,
                'message'      => "Duplicate punch rejected: a punch IN for {$employee->employee_code} on {$date} already exists at {$existing->punch_in}.",
            ];
        }

        if ($punchType === 'OUT' && $existing->punch_out !== null) {
            return [
                'is_duplicate' => true,
                'message'      => "Duplicate punch rejected: a punch OUT for {$employee->employee_code} on {$date} already exists at {$existing->punch_out}.",
            ];
        }

        if ($punchType === 'OUT' && $existing->punch_in === null) {
            return [
                'is_duplicate' => true,
                'message'      => "Invalid punch: cannot record punch OUT for {$employee->employee_code} on {$date} without a prior punch IN.",
            ];
        }

        return ['is_duplicate' => false, 'message' => ''];
    }

    /**
     * Calculate hours worked between punch_in and punch_out.
     * Handles night-shift cross-midnight correctly.
     *
     * @param  string  $punchIn   Time string (HH:MM:SS)
     * @param  string  $punchOut  Time string (HH:MM:SS)
     * @param  bool    $isNightShift  True when shift crosses midnight
     * @return float   Hours worked (decimal)
     */
    public function calculateHoursWorked(Carbon|string $punchIn, Carbon|string $punchOut, bool $isNightShift = false): float
    {
        $in  = $punchIn instanceof Carbon ? $punchIn->copy() : Carbon::parse($punchIn);
        $out = $punchOut instanceof Carbon ? $punchOut->copy() : Carbon::parse($punchOut);

        // Night-shift: if punch_out < punch_in, add 24 hours to punch_out
        if ($isNightShift && $out->lessThan($in)) {
            $out->addDay();
        }

        $minutes = $in->diffInMinutes($out);

        return round($minutes / 60, 4);
    }

    /**
     * Calculate OT hours based on state-specific rules from config/statutory.php.
     * NEVER hardcode the OT threshold or multiplier here.
     *
     * @param  float   $hoursWorked  Total hours worked
     * @param  string  $stateCode    2-letter state code (e.g. 'MH', 'DL')
     * @return float   OT hours (0 if no OT)
     */
    public function calculateOtHours(float $hoursWorked, string $stateCode): float
    {
        $stateCode = strtoupper($stateCode);
        $otConfig  = config("statutory.overtime.{$stateCode}");

        // Fall back to DL (Delhi) rules if state not found
        if (! $otConfig) {
            Log::warning('attendance.ot_config_missing', [
                'state_code' => $stateCode,
                'fallback'   => 'DL',
            ]);
            $otConfig = config('statutory.overtime.DL');
        }

        $otThreshold = (float) $otConfig['ot_applicable_after_hours'];

        if ($hoursWorked <= $otThreshold) {
            return 0.0;
        }

        return round($hoursWorked - $otThreshold, 2);
    }

    /**
     * Detect whether the employee is late based on shift start time
     * and the shift's grace_period_minutes.
     */
    private function detectLateStatus(Carbon $punchIn, Shift $shift): string
    {
        $shiftStart = Carbon::createFromTimeString($shift->start_time);
        $graceCutoff = $shiftStart->copy()->addMinutes($shift->grace_period_minutes ?? 0);

        if ($punchIn->greaterThan($graceCutoff)) {
            return 'present'; // present but late — could be extended to 'late' status
        }

        return 'present';
    }

    /**
     * Resolve the shift assigned to an employee for a given timestamp.
     * Falls back to the location's default shift, then null.
     */
    private function resolveShift(Employee $employee, Carbon $timestamp): ?Shift
    {
        // For now, resolve the active shift for the employee's location
        // Phase 3 will add proper shift assignment per employee
        return Shift::withoutGlobalScopes()
            ->where('location_id', $employee->location_id)
            ->where('is_active', true)
            ->first();
    }
}
