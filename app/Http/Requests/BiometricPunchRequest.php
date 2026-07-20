<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BiometricPunchRequest
 *
 * Validates the JSON payload for the mock biometric punch endpoint.
 *
 * Schema (documented in docs/biometric-mock-api.md):
 * {
 *   "employee_code": "EMP-MH-00001",
 *   "punch_type":    "IN",
 *   "timestamp":     "2026-07-20T09:15:00+05:30",
 *   "latitude":      19.0760,
 *   "longitude":     72.8777,
 *   "device_id":     "ZK-MOCK-01"
 * }
 */
class BiometricPunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->hasAnyRole(['super_admin', 'central_hr', 'location_hr']);
    }

    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:20', 'regex:/^EMP-[A-Z]{2}-\d{5}$/'],
            'punch_type'    => ['required', 'string', 'in:IN,OUT'],
            'timestamp'     => ['required', 'string', 'date'],
            'latitude'      => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'     => ['nullable', 'numeric', 'between:-180,180'],
            'device_id'     => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_code.regex'  => 'employee_code must follow the format EMP-{STATE_CODE}-{SEQUENCE} (e.g. EMP-MH-00001).',
            'punch_type.in'        => 'punch_type must be either IN or OUT.',
            'timestamp.date'       => 'timestamp must be a valid ISO 8601 date-time string.',
            'latitude.between'     => 'latitude must be between -90 and 90.',
            'longitude.between'    => 'longitude must be between -180 and 180.',
        ];
    }
}
