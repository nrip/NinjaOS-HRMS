<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Employee;
use App\Services\Attendance\AttendanceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessBiometricPunch
 *
 * Queued job that processes a biometric device punch payload.
 * Dispatched by the mock biometric endpoint (POST /api/v1/integrations/biometric/mock-punch)
 * and will also be dispatched by real ZKTeco/eSSL device webhooks in production.
 *
 * Queue: biometric (high-priority, dedicated Horizon worker)
 * Retries: 3 attempts with exponential backoff
 */
class ProcessBiometricPunch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public readonly string $employeeCode,
        public readonly string $punchType,
        public readonly string $timestamp,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly string $deviceId,
    ) {
        $this->onQueue(config('nexusos.biometric.queue_name', 'biometric'));
    }

    public function handle(AttendanceService $attendanceService): void
    {
        $employee = Employee::withoutGlobalScopes()
            ->where('employee_code', $this->employeeCode)
            ->with('location')
            ->first();

        if (! $employee) {
            Log::channel('audit')->warning('biometric.employee_not_found', [
                'employee_code' => $this->employeeCode,
                'device_id'     => $this->deviceId,
            ]);
            $this->fail(new \RuntimeException("Employee not found: {$this->employeeCode}"));
            return;
        }

        $punchTime = Carbon::parse($this->timestamp);

        $result = $attendanceService->processPunch(
            employee:    $employee,
            punchType:   $this->punchType,
            timestamp:   $punchTime,
            punchSource: 'biometric',
            latitude:    $this->latitude,
            longitude:   $this->longitude,
            deviceId:    $this->deviceId,
        );

        if (! $result['success']) {
            Log::channel('audit')->warning('biometric.punch_rejected', [
                'employee_id'   => $employee->id,
                'employee_code' => $this->employeeCode,
                'punch_type'    => $this->punchType,
                'device_id'     => $this->deviceId,
                'reason'        => $result['message'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('audit')->error('biometric.job_failed', [
            'employee_code' => $this->employeeCode,
            'punch_type'    => $this->punchType,
            'device_id'     => $this->deviceId,
            'error'         => $exception->getMessage(),
        ]);
    }
}
