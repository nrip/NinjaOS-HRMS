<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BiometricPunchRequest;
use App\Jobs\ProcessBiometricPunch;
use Illuminate\Http\JsonResponse;

/**
 * BiometricMockController
 *
 * Provides a mock biometric punch endpoint for development and testing.
 * In production, this endpoint is replaced by the actual ZKTeco/eSSL device webhook.
 *
 * Endpoint: POST /api/v1/integrations/biometric/mock-punch
 * Auth:     Sanctum token (super_admin, central_hr, location_hr)
 * Docs:     docs/biometric-mock-api.md
 */
class BiometricMockController extends Controller
{
    /**
     * Accept a mock biometric punch and dispatch it to the queue.
     *
     * The job is dispatched to the 'biometric' queue (high-priority Horizon worker).
     * The response returns a 202 Accepted so the frontend can poll for completion.
     */
    public function punch(BiometricPunchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $job = new ProcessBiometricPunch(
            employeeCode: $validated['employee_code'],
            punchType:    $validated['punch_type'],
            timestamp:    $validated['timestamp'],
            latitude:     isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude:    isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            deviceId:     $validated['device_id'],
        );

        dispatch($job);

        return response()->json([
            'status'        => 'queued',
            'message'       => 'Biometric punch accepted and queued for processing.',
            'employee_code' => $validated['employee_code'],
            'punch_type'    => $validated['punch_type'],
            'timestamp'     => $validated['timestamp'],
            'device_id'     => $validated['device_id'],
        ], 202);
    }
}
