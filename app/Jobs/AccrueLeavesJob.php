<?php

namespace App\Jobs;

use App\Models\Location;
use App\Services\Leave\LeaveAccrualEngine;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * AccrueLeavesJob
 *
 * Dispatched by the Laravel Scheduler on the first day of each month.
 * For each active location, runs the monthly accrual engine and expires
 * any Comp Off balances that have passed their expiry_date.
 *
 * The job is location-scoped so it can be parallelised per location if needed.
 */
class AccrueLeavesJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly ?int $locationId = null,
        public readonly ?int $year       = null,
        public readonly ?int $month      = null,
    ) {}

    public function handle(LeaveAccrualEngine $engine): void
    {
        $now   = Carbon::now();
        $year  = $this->year  ?? $now->year;
        $month = $this->month ?? $now->month;

        $locations = Location::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($this->locationId, fn ($q) => $q->where('id', $this->locationId))
            ->get();

        foreach ($locations as $location) {
            $stateCode = $location->state_code ?? 'default';

            Log::info('AccrueLeavesJob: Running monthly accrual', [
                'location_id' => $location->id,
                'state_code'  => $stateCode,
                'year'        => $year,
                'month'       => $month,
            ]);

            $engine->runMonthlyAccrual(
                locationId: $location->id,
                stateCode:  $stateCode,
                year:       $year,
                month:      $month,
            );
        }

        // Expire all Comp Off balances whose expiry_date has passed
        $expiredDays = $engine->expireCompOffBalances($now);

        Log::info('AccrueLeavesJob: Completed', [
            'locations_processed' => $locations->count(),
            'comp_off_days_expired' => $expiredDays,
        ]);
    }
}
