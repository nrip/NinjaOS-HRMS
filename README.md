# NinjaOS HRMS — NexusOS

A multi-location HRMS for Indian enterprises built with Laravel 11, enforcing strict location-based multi-tenancy.

## Tech Stack

- **Backend:** Laravel 11, PHP 8.3+
- **Database:** MySQL 8.0+ (SQLite for testing)
- **Cache/Queue:** Redis 7
- **Auth:** Laravel Sanctum (token-based)
- **RBAC:** Spatie Permission
- **Audit:** Spatie ActivityLog
- **Documents:** Spatie MediaLibrary
- **Import/Export:** Maatwebsite Excel
- **Testing:** Pest PHP + Larastan
- **Frontend:** Bootstrap 5.3, Alpine.js 3.x, DataTables, jQuery 3.7

## Multi-Tenancy Architecture

All tenant-scoped models are automatically filtered by `location_id` via a `LocationScope` global scope. The `TenantContext` singleton is set per-request by the `EnforceLocationScope` middleware.

```
Request → EnforceLocationScope → TenantContext::setLocationId()
                                        ↓
                            LocationScope::apply() → WHERE location_id = ?
```

## Roles

| Role | Access |
|------|--------|
| Super Admin | All locations, all modules |
| Central HR | All locations, HR modules |
| Location HR | Single location, HR modules |
| Manager | Own team only |
| Employee | Self-service |
| Payroll Admin | Payroll processing |
| Auditor | Read-only |
| Recruiter | ATS only |

## Setup

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed
php artisan migrate --seed

# Start development server
php artisan serve
```

## Testing

```bash
# Run Phase 1 Exit Gate tests
php artisan test tests/Feature/Phase1ExitGateTest.php

# Run all tests
php artisan test
```

## Phase Status

| Phase | Status | Description |
|-------|--------|-------------|
| Phase 0 | ✅ Complete | Foundation, multi-tenancy, RBAC, statutory config |
| Phase 1 | ✅ Complete | Core HR: Employee CRUD, lifecycle, CSV import, audit log |
| Phase 2 | 🔜 Planned | Attendance & Shift Management |
| Phase 3 | 🔜 Planned | Leave Management |
| Phase 4 | 🔜 Planned | Payroll & Statutory |
| Phase 5 | 🔜 Planned | Recruitment (ATS) |

## Statutory Configuration

All statutory values (PF, ESI, PT for 9 states, TDS, Gratuity, Bonus) are in `config/statutory.php`. Never hardcoded in business logic.

## Compliance

- PF wage ceiling: ₹15,000
- ESI wage ceiling: ₹21,000 (₹25,000 for disabled)
- Professional Tax: configured for all 9 states
- Gratuity ceiling: ₹20 Lakh
- Bonus Act ceiling: ₹21,000
