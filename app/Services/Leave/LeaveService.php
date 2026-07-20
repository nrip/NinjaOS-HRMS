<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\HolidayCalendar;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeaveService
 *
 * Handles:
 *   - Working days calculation (excludes weekends and location holidays)
 *   - Half-day leave support (0.5 day deduction)
 *   - Leave application submission with tentative balance deduction
 *   - Approval workflow: approve / reject with balance mutation
 *   - LocationScope enforcement (Location HR can only act on their own location)
 */
class LeaveService
{
    /**
     * Count working days between two dates (inclusive) for a given location.
     *
     * Excludes:
     *   - Saturdays and Sundays
     *   - Any holiday in the location's HolidayCalendar (national or state)
     *
     * If $isHalfDay is true and fromDate === toDate, returns 0.5.
     *
     * @param Location $location
     * @param Carbon   $fromDate
     * @param Carbon   $toDate
     * @param bool     $isHalfDay
     * @return int|float  Returns int for whole-day counts, float (0.5) for half-day
     */
    public function countWorkingDays(
        Location $location,
        Carbon $fromDate,
        Carbon $toDate,
        bool $isHalfDay = false,
    ): int|float {
        // Fetch all holiday dates for this location in the date range
        $holidayDates = HolidayCalendar::withoutGlobalScopes()
            ->where('location_id', $location->id)
            ->where('is_active', true)
            ->whereBetween('holiday_date', [
                $fromDate->toDateString(),
                $toDate->toDateString(),
            ])
            ->pluck('holiday_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->toArray();

        $workingDays = 0;

        foreach (CarbonPeriod::create($fromDate, $toDate) as $day) {
            // Skip weekends
            if ($day->isWeekend()) {
                continue;
            }

            // Skip holidays
            if (in_array($day->toDateString(), $holidayDates)) {
                continue;
            }

            $workingDays++;
        }

        // Half-day: the single day counts as 0.5 if it is a working day
        if ($isHalfDay) {
            return $workingDays > 0 ? 0.5 : 0.0;
        }

        return $workingDays;
    }

    /**
     * Apply for leave.
     *
     * Validates balance availability, calculates working days (excluding holidays),
     * creates the LeaveApplication with status = pending_approval, and deducts
     * the days from the pending bucket of the employee's LeaveBalance.
     *
     * @throws \RuntimeException if insufficient balance or invalid dates
     */
    public function applyLeave(
        Employee $employee,
        string $leaveType,
        Carbon $fromDate,
        Carbon $toDate,
        string $reason,
        bool $isHalfDay = false,
        ?string $halfDaySession = null,
    ): LeaveApplication {
        $location = Location::withoutGlobalScopes()->findOrFail($employee->location_id);

        $numberOfDays = $this->countWorkingDays(
            location:  $location,
            fromDate:  $fromDate,
            toDate:    $toDate,
            isHalfDay: $isHalfDay,
        );

        if ($numberOfDays <= 0) {
            throw new \RuntimeException('The selected date range contains no working days.');
        }

        $year = $fromDate->year;

        return DB::transaction(function () use (
            $employee, $location, $leaveType, $fromDate, $toDate,
            $numberOfDays, $reason, $isHalfDay, $halfDaySession, $year
        ) {
            // For leave types other than Unpaid Leave, validate balance
            if ($leaveType !== 'UL') {
                $balance = LeaveBalance::withoutGlobalScopes()
                    ->where('employee_id', $employee->id)
                    ->where('leave_type', $leaveType)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (! $balance || ! $balance->hasSufficientBalance($numberOfDays)) {
                    throw new \RuntimeException(
                        "Insufficient {$leaveType} balance. Available: "
                        . ($balance ? $balance->closing_balance : 0)
                        . ", Requested: {$numberOfDays}"
                    );
                }

                // Tentative deduction into pending bucket
                $balance->deductPending($numberOfDays);
            }

            $application = LeaveApplication::withoutGlobalScopes()->create([
                'leave_id'        => (string) \Illuminate\Support\Str::uuid(),
                'location_id'     => $location->id,
                'employee_id'     => $employee->id,
                'leave_type'      => $leaveType,
                'from_date'       => $fromDate->toDateString(),
                'to_date'         => $toDate->toDateString(),
                'number_of_days'  => $numberOfDays,
                'is_half_day'     => $isHalfDay,
                'half_day_session'=> $isHalfDay ? $halfDaySession : null,
                'reason'          => $reason,
                'status'          => 'pending_approval',
            ]);

            Log::info('LeaveService: Leave application submitted', [
                'application_id' => $application->id,
                'employee_id'    => $employee->id,
                'leave_type'     => $leaveType,
                'days'           => $numberOfDays,
            ]);

            return $application;
        });
    }

    /**
     * Approve a leave application.
     *
     * Moves days from pending → availed in the LeaveBalance.
     * Records the approver and timestamp. Logs the event via Spatie ActivityLog.
     *
     * @throws \RuntimeException if the application is not in pending_approval state
     */
    public function approveLeave(
        LeaveApplication $application,
        User $approver,
        string $comments = '',
    ): LeaveApplication {
        if (! $application->canBeApproved()) {
            throw new \RuntimeException(
                "Cannot approve a leave application in status: {$application->status}"
            );
        }

        return DB::transaction(function () use ($application, $approver, $comments) {
            $numberOfDays = (float) $application->number_of_days;

            // Move from pending → availed (only for non-UL leave types)
            if ($application->leave_type !== 'UL') {
                $balance = LeaveBalance::withoutGlobalScopes()
                    ->where('employee_id', $application->employee_id)
                    ->where('leave_type', $application->leave_type)
                    ->where('year', $application->from_date->year)
                    ->lockForUpdate()
                    ->first();

                if ($balance) {
                    $balance->confirmAvailed($numberOfDays);
                }
            }

            $application->status           = 'approved';
            $application->approved_by      = $approver->id;
            $application->approval_comments= $comments;
            $application->approved_at      = now();
            $application->save();

            // Spatie ActivityLog records this via LogsActivity trait on the model
            activity('leave')
                ->performedOn($application)
                ->causedBy($approver)
                ->withProperties(['days' => $numberOfDays, 'comments' => $comments])
                ->event('approved')
                ->log("Leave approved by {$approver->name}");

            return $application;
        });
    }

    /**
     * Reject a leave application.
     *
     * Restores the tentative pending days back to the available balance.
     * Records the rejector and timestamp.
     *
     * @throws \RuntimeException if the application is not in pending_approval state
     */
    public function rejectLeave(
        LeaveApplication $application,
        User $approver,
        string $comments = '',
    ): LeaveApplication {
        if (! $application->canBeRejected()) {
            throw new \RuntimeException(
                "Cannot reject a leave application in status: {$application->status}"
            );
        }

        return DB::transaction(function () use ($application, $approver, $comments) {
            $numberOfDays = (float) $application->number_of_days;

            // Restore pending days back to available (only for non-UL leave types)
            if ($application->leave_type !== 'UL') {
                $balance = LeaveBalance::withoutGlobalScopes()
                    ->where('employee_id', $application->employee_id)
                    ->where('leave_type', $application->leave_type)
                    ->where('year', $application->from_date->year)
                    ->lockForUpdate()
                    ->first();

                if ($balance) {
                    $balance->restorePending($numberOfDays);
                }
            }

            $application->status      = 'rejected';
            $application->rejected_by = $approver->id;
            $application->rejected_at = now();
            $application->approval_comments = $comments;
            $application->save();

            activity('leave')
                ->performedOn($application)
                ->causedBy($approver)
                ->withProperties(['days' => $numberOfDays, 'comments' => $comments])
                ->event('rejected')
                ->log("Leave rejected by {$approver->name}");

            return $application;
        });
    }

    /**
     * Cancel a leave application (by the employee or HR).
     *
     * If the application was pending, restores the pending days.
     * If the application was approved and the leave is in the future, restores availed days.
     */
    public function cancelLeave(
        LeaveApplication $application,
        User $cancelledBy,
    ): LeaveApplication {
        if (! $application->canBeCancelled()) {
            throw new \RuntimeException(
                "Cannot cancel a leave application in status: {$application->status}"
            );
        }

        return DB::transaction(function () use ($application, $cancelledBy) {
            $numberOfDays = (float) $application->number_of_days;

            if ($application->leave_type !== 'UL') {
                $balance = LeaveBalance::withoutGlobalScopes()
                    ->where('employee_id', $application->employee_id)
                    ->where('leave_type', $application->leave_type)
                    ->where('year', $application->from_date->year)
                    ->lockForUpdate()
                    ->first();

                if ($balance) {
                    if ($application->isPending()) {
                        $balance->restorePending($numberOfDays);
                    } elseif ($application->isApproved()) {
                        // Reverse availed days
                        $balance->availed = round(max(0.0, (float) $balance->availed - $numberOfDays), 2);
                        $balance->recomputeClosing();
                    }
                }
            }

            $application->status       = 'cancelled';
            $application->cancelled_at = now();
            $application->save();

            return $application;
        });
    }
}
