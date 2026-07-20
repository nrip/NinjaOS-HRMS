# NinjaOS HRMS — Mobile App

React Native + Expo mobile application for NinjaOS HRMS.

## Architecture

```
mobile/
├── App.tsx                        # Entry point — renders AppNavigator
├── app.json                       # Expo config (apiBaseUrl in extra)
├── src/
│   ├── navigation/
│   │   └── AppNavigator.tsx       # Auth guard + Bottom Tab Navigator
│   ├── screens/
│   │   ├── LoginScreen.tsx        # Sanctum login with expo-secure-store
│   │   ├── AttendanceScreen.tsx   # Punch IN/OUT with geo-fencing UI
│   │   ├── LeaveScreen.tsx        # Balance display + application form
│   │   └── PayslipScreen.tsx      # Payslip list + signed URL PDF viewer
│   ├── services/
│   │   ├── api.ts                 # Axios instance with Sanctum interceptors
│   │   ├── authService.ts         # Login, logout, profile fetch
│   │   ├── attendanceService.ts   # Punch + GPS coordinates
│   │   ├── leaveService.ts        # Balances + application submission
│   │   └── payslipService.ts      # Payslip list + signed URL PDF open
│   └── store/
│       └── authStore.ts           # Zustand global auth state
```

## Security

| Concern | Implementation |
|---|---|
| Token storage | `expo-secure-store` (iOS Keychain / Android Keystore, AES-256) |
| Token transmission | `Authorization: Bearer <token>` header on every request |
| Token expiry | 401 interceptor clears token and triggers nav guard to Login |
| PII in logs | Email and password are never logged |
| Payslip PDF | Signed URL (15-min TTL) — no PDF data in app state |

## API Contract

All requests go to `{apiBaseUrl}/api/v1/`. Set `apiBaseUrl` in `app.json`:

```json
{
  "expo": {
    "extra": {
      "apiBaseUrl": "https://your-domain.com"
    }
  }
}
```

### Auth endpoints

| Method | Path | Body | Response |
|---|---|---|---|
| POST | `/auth/login` | `{email, password}` | `{token, user}` |
| POST | `/auth/logout` | — | `{message}` |
| GET | `/auth/me` | — | `{data: AuthUser}` |

### Attendance endpoints

| Method | Path | Body | Response |
|---|---|---|---|
| POST | `/attendance/punch` | `{employee_id, punch_type, latitude?, longitude?}` | `{success, message}` |
| GET | `/attendance` | `?employee_id=&date=` | `{data: [AttendanceRecord]}` |

### Leave endpoints

| Method | Path | Body | Response |
|---|---|---|---|
| GET | `/leave/balances` | `?employee_id=` | `{data: [LeaveBalance]}` |
| POST | `/leave/apply` | `{employee_id, leave_type, from_date, to_date, reason, is_half_day?}` | `{success, message}` |
| GET | `/leave` | `?employee_id=` | `{data: [LeaveApplication]}` |

### Payslip endpoints

| Method | Path | Response |
|---|---|---|
| GET | `/payroll/payslips?employee_id=` | `{data: [PayslipSummary]}` |

`PayslipSummary.payslip_url` is a Sanctum-signed URL valid for 15 minutes.

## Running locally

```bash
cd mobile
pnpm install
pnpm start          # Opens Expo Dev Tools
pnpm android        # Run on Android emulator
pnpm ios            # Run on iOS simulator (macOS only)
```

## Geo-fencing

The attendance punch API enforces geo-fencing server-side. If the employee's
location has `attendance_radius_meters > 0`, the API returns HTTP 422 when
`latitude` and `longitude` are missing. The mobile app requests GPS permission
on the Attendance screen and passes coordinates automatically.
