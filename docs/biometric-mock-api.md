# Biometric Mock API — NexusOS

## Overview

The Biometric Mock API simulates real-time biometric device punches (ZKTeco, eSSL, etc.) during development and testing. In production, this endpoint is replaced by the actual device webhook.

**Endpoint:** `POST /api/v1/integrations/biometric/mock-punch`  
**Auth:** Sanctum Bearer Token (roles: `super_admin`, `central_hr`, `location_hr`)  
**Queue:** `biometric` (high-priority Horizon worker, 3 retries, 10s backoff)

---

## Request Schema

```json
{
  "employee_code": "EMP-MH-00001",
  "punch_type":    "IN",
  "timestamp":     "2026-07-20T09:15:00+05:30",
  "latitude":      19.0760,
  "longitude":     72.8777,
  "device_id":     "ZK-MOCK-01"
}
```

### Field Definitions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `employee_code` | string | ✅ | Employee code in format `EMP-{STATE_CODE}-{SEQUENCE}` (e.g. `EMP-MH-00001`) |
| `punch_type` | string | ✅ | `IN` or `OUT` only |
| `timestamp` | string | ✅ | ISO 8601 datetime with timezone (e.g. `2026-07-20T09:15:00+05:30`) |
| `latitude` | float | ❌ | GPS latitude (-90 to 90). If null, geo-fencing check is skipped |
| `longitude` | float | ❌ | GPS longitude (-180 to 180). If null, geo-fencing check is skipped |
| `device_id` | string | ✅ | Device identifier (max 64 chars, e.g. `ZK-MOCK-01`, `ESSL-GATE-02`) |

---

## Response — Success (202 Accepted)

```json
{
  "status":        "queued",
  "message":       "Biometric punch accepted and queued for processing.",
  "employee_code": "EMP-MH-00001",
  "punch_type":    "IN",
  "timestamp":     "2026-07-20T09:15:00+05:30",
  "device_id":     "ZK-MOCK-01"
}
```

The `202 Accepted` response means the punch has been placed on the `biometric` queue. The actual processing happens asynchronously via `ProcessBiometricPunch` job.

---

## Response — Validation Error (422 Unprocessable Entity)

```json
{
  "message": "The employee_code field format is invalid.",
  "errors": {
    "employee_code": [
      "employee_code must follow the format EMP-{STATE_CODE}-{SEQUENCE} (e.g. EMP-MH-00001)."
    ]
  }
}
```

---

## Response — Unauthorized (403 Forbidden)

```json
{
  "message": "This action is unauthorized."
}
```

---

## Geo-Fencing Behaviour

When `latitude` and `longitude` are provided, the `ProcessBiometricPunch` job calls `GeoFencingService::isWithinRadius()` using the **Haversine formula** to calculate the distance between the punch coordinates and the location's `gis_lat`/`gis_lng`.

- The allowed radius is configurable per location via `locations.attendance_radius_meters` (default: `100` metres from `config/nexusos.php`).
- If the punch is **outside** the allowed radius, it is **rejected** with reason `geo_fence_violation` and logged to the `audit` channel.
- If GPS coordinates are **not provided** (null), the geo-fencing check is **skipped** and the punch is processed normally.

---

## Duplicate Punch Detection (SRS FR3.1.7)

Before processing, `AttendanceService` checks if a punch of the **same type** (`IN` or `OUT`) already exists for the employee on the **same date**. If a duplicate is found:
- The punch is **rejected** with message: `"Duplicate punch: an IN punch already exists for this employee today."`
- The rejection is logged to the `audit` channel.
- No new attendance record is created.

---

## Night Shift Cross-Midnight Logic

For night shifts (`is_night_shift = true`), an `OUT` punch after midnight (00:00–06:00) is attributed to the **previous calendar day's** attendance record. This ensures correct total hours calculation for shifts like 22:00–06:00.

---

## OT Calculation

Overtime is calculated exclusively from `config/statutory.php` under the `overtime` key, keyed by state code (e.g. `MH`, `KA`). The formula is:

```
ot_hours = max(0, total_hours - ot_applicable_after_hours)
```

The OT rate multiplier (e.g. `1.5` or `2.0`) is stored in config and used by the Payroll module in Phase 3.

---

## Example: cURL

```bash
curl -X POST https://your-domain.com/api/v1/integrations/biometric/mock-punch \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_code": "EMP-MH-00001",
    "punch_type": "IN",
    "timestamp": "2026-07-20T09:15:00+05:30",
    "latitude": 19.0760,
    "longitude": 72.8777,
    "device_id": "ZK-MOCK-01"
  }'
```

---

## Mock Device IDs

Configured in `config/nexusos.php` under `biometric.mock_device_ids`:

| Device ID | Location |
|-----------|----------|
| `ZK-MOCK-01` | Mumbai HQ |
| `ZK-MOCK-02` | Pune Office |
| `ESSL-MOCK-01` | Delhi NCR |
| `ESSL-MOCK-02` | Bengaluru Tech Park |

---

## Queue Monitoring

Use Laravel Horizon to monitor the `biometric` queue:

```
http://your-domain.com/horizon
```

Failed jobs are logged to `storage/logs/laravel.log` and the `audit` channel.
