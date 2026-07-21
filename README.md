***

#  NinjaOS HRMS

**A production-grade, multi-location Human Resource Management System engineered specifically for the Indian statutory and compliance landscape.**

[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php)](https://www.php.net)
[![MySQL 8](https://img.shields.io/badge/MySQL-8-00758F?style=flat-square&logo=mysql)](https://www.mysql.com)
[![React Native](https://img.shields.io/badge/React_Native-Expo-61DAFB?style=flat-square&logo=react)](https://reactnative.dev)
[![Tests](https://img.shields.io/badge/Tests-50_Passing-28A745?style=flat-square)](./tests)
[![Coverage](https://img.shields.io/badge/Statutory_Engine-100%25_Coverage-28A745?style=flat-square)](./app/Services/StatutoryEngine)

---

## 📑 Table of Contents
- [Overview](#overview)
- [Key Features](#key-features)
- [Architecture & Design Decisions](#architecture--design-decisions)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Getting Started (Local Development)](#getting-started-local-development)
- [Testing & Quality Assurance](#testing--quality-assurance)
- [Deployment & Operations](#deployment--operations)
- [Documentation Hub](#documentation-hub)
- [Development Guidelines](#development-guidelines)

---

## 🌟 Overview

Indian enterprises operating across multiple states face a uniquely complex HR challenge: statutory compliance rules (PF, ESI, Professional Tax, TDS), leave accrual rules, and holiday calendars differ by state. Most off-the-shelf HRMS products treat India as a monolithic entity.

**NinjaOS HRMS** was designed from the ground up to be **state-aware and location-scoped**. Every data record—whether an employee, leave application, payroll run, or job requisition—is bound to a location. A Location HR manager in Pune can only ever see data for Pune. This is not a UI filter; it is a database-level global scope enforced on every query, API call, and background job.

---

## 🚀 Key Features

- **Core HR & Lifecycle:** Complete employee master with a state-machine-driven lifecycle (Onboarding → Probation → Confirmed → Transfer → Exit). Auto-generated `EMP-{STATE}-{SEQ}` codes.
- **Attendance & Geo-Fencing:** Biometric integration (ZKTeco/Matrix) and mobile GPS punch-ins validated via the Haversine formula. Night-shift cross-midnight logic included.
- **Leave Management:** State-specific accrual engines, half-day support, Comp-Off expiry, and 12-month forward balance projections.
- **Payroll & Statutory Engine:** 100% config-driven calculation of PF, ESI, PT (9 states), TDS, Gratuity, and Bonus. Zero hardcoded numbers. Includes a Variance Report to block finalization if net pay changes >5%.
- **Applicant Tracking System (ATS):** Kanban pipeline, mock resume parsing, and a one-click "Convert to Employee" handoff that bridges recruitment to Core HR.
- **Integrations:** Interface-driven mock services for WhatsApp Business API, Tally/Zoho Accounting, and HDFC NEFT bank files. Swappable to real APIs via a single service container binding.
- **Mobile App:** React Native (Expo) app with secure token storage (`expo-secure-store`), GPS attendance, and signed-URL payslip viewing.

---

## 🏗️ Architecture & Design Decisions

### 1. Multi-Tenancy via `LocationScope`
Since MySQL lacks PostgreSQL's Row-Level Security (RLS), we enforce tenant isolation at the application layer using Laravel Global Scopes.
- **`TenantContext` Singleton:** Extracts `location_id` from the Sanctum JWT.
- **`LocationScope`:** Auto-appends `WHERE location_id = ?` to all tenant-scoped models.
- **Safety Net:** A custom Pest architecture test fails the CI pipeline if any tenant-scoped model is queried without the scope.

### 2. Isolated Statutory Engine
Payroll accuracy is non-negotiable. The calculation logic lives in `app/Services/StatutoryEngine/` as **pure PHP classes**.
- They accept `PayrollInputDTO` (Data Transfer Objects) and return calculated arrays.
- **Zero Eloquent calls, zero database queries, zero side effects.**
- This makes the engine trivially unit-testable and auditable by statutory consultants who don't know Laravel.

### 3. Interface-Driven Integrations
External integrations (WhatsApp, Accounting, Banking) use PHP Interfaces. The mock implementations log payloads to `storage/logs/`. Swapping to production APIs requires changing only the binding in `AppServiceProvider.php`.

### 4. Pragmatic Technology Choices
- **DomPDF over Snappy:** Chosen to eliminate the operational complexity of the `wkhtmltopdf` system binary in containerized environments.
- **Signed URLs for Payslips:** The API returns a 15-minute TTL signed URL instead of base64 PDF data, reducing mobile payload from ~267KB to <1KB per record.

---

## 🛠️ Technology Stack

| Layer | Technology |
| :--- | :--- |
| **Backend Framework** | Laravel 11 (PHP 8.3) |
| **Database** | MySQL 8.0 |
| **Cache & Queue** | Redis 7 + Laravel Horizon |
| **Authentication** | Laravel Sanctum (API/Mobile) |
| **Web Frontend** | Blade Components + Bootstrap 5.3 + Alpine.js 3.x |
| **Mobile App** | React Native + Expo (Managed Workflow) |
| **Testing** | Pest PHP (Unit/Feature), Playwright (E2E) |
| **Media/Files** | Spatie MediaLibrary + Laravel Flysystem (S3) |
| **Audit & Logs** | Spatie ActivityLog + Monolog (JSON) |

---

## 📁 Project Structure

```text
ninjaos-hrms/
── app/
│   ├── Console/Commands/      # Artisan commands (e.g., Leave Accruals)
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/        # EnforceLocationScope, AuditCrossLocation
│   │   └── Requests/          # Form Requests (Validation)
│   ├── Models/
│   │   └── Scopes/            # LocationScope.php (Global Scope)
│   └── Services/
│       ├── StatutoryEngine/   # PURE PHP: PfCalculator, TdsCalculator, etc.
│       ├── Attendance/        # GeoFencingService, AttendanceService
│       ├── Leave/             # LeaveAccrualEngine, LeaveProjectionService
│       └── Integrations/      # Interfaces & Mock Implementations
├── config/
│   ├── statutory.php          # ALL PF, ESI, PT, TDS ceilings and rates
│   └── nexusos.php            # App-specific config (geo-fence defaults)
── database/
│   ├── migrations/            # Additive migrations only (never drop columns)
│   └── seeders/               # 16 locations, 8 RBAC roles, statutory config
├── mobile/                    # React Native (Expo) application
├── resources/views/           # Blade templates + Bootstrap/Alpine
├── routes/
│   ├── web.php                # Web UI routes
│   └── api.php                # Mobile/API routes (auth:sanctum)
├── tests/
│   ├── Feature/               # Phase 1-6 Exit Gate tests
│   ── Unit/StatutoryEngine/  # 100% coverage payroll math tests
── docs/                      # Architecture diagrams, API specs, runbooks
```

---

##  Getting Started (Local Development)

### Prerequisites
- PHP 8.3+ (with `bcmath`, `redis`, `zip`, `xml` extensions)
- MySQL 8.0+
- Redis 7.0+
- Node.js 22.x & npm/pnpm
- Composer 2.7+

### Installation

1. **Clone the repository:**
   ```bash
   git clone git@github.com:nrip/NinjaOS-HRMS.git
   cd NinjaOS-HRMS
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Update `.env` with your local MySQL and Redis credentials.*

4. **Seed database and run migrations:**
   ```bash
   php artisan migrate --seed
   ```
   *This seeds 16 locations across 9 states, 8 RBAC roles, and statutory configurations.*

5. **Run the application:**
   ```bash
   php artisan serve
   # Or use Laravel Valet / Sail if preferred
   ```
   Access the app at `http://localhost:8000`.

6. **Run the Mobile App (Optional):**
   ```bash
   cd mobile
   npm install
   npx expo start
   ```

---

## 🧪 Testing & Quality Assurance

NinjaOS follows a strict **Test-First Development** methodology. The test suite is not a vanity metric; it is a precise specification of business rules.

- **Total Tests:** 50
- **Total Assertions:** 330
- **Statutory Engine Coverage:** 100%

**Run the full test suite:**
```bash
php artisan test --no-coverage
```

**Run specific phase tests:**
```bash
php artisan test tests/Feature/Phase4PayrollTest.php
php artisan test tests/Unit/StatutoryEngineTest.php
```

---

##  Deployment & Operations

For production deployment, refer to the comprehensive **[Production Deployment Runbook](./docs/NinjaOS_HRMS_Production_Deployment_Runbook.md)**.

**High-Level Production Architecture:**
- **Compute:** AWS EC2 (Ubuntu 24.04) or Elastic Beanstalk
- **Database:** Amazon RDS MySQL 8.0 (Multi-AZ)
- **Cache/Queue:** Amazon ElastiCache Redis
- **Storage:** Amazon S3 (Media, Resumes, Backups)
- **Queue Management:** Laravel Horizon via Supervisor
- **Backups:** Spatie Backup (Daily to S3)

---

## 📚 Documentation Hub

All project documentation is stored in the `docs/` directory or linked below:

| Document | Description |
| :--- | :--- |
| **[Product Requirements (PRD)](./docs/NexusOS_HRMS_PRD.docx)** | Business requirements, user personas, and scope. |
| **[Software Requirements (SRS v2.0)](./docs/NexusOS_HRMS_SRS_v2.0.docx)** | Technical specifications, API contracts, and data models. |
| **[Deployment Runbook](./docs/NinjaOS_HRMS_Production_Deployment_Runbook.md)** | Step-by-step AWS/DevOps deployment and local setup guide. |
| **[Presentation Script](./docs/NinjaOS_HRMS_Presentation_Script.md)** | 14-slide stakeholder presentation and engineering decisions. |
| **[Phase Proposals](./docs/)** | Detailed implementation proposals for Phases 4, 5, and 6. |
| **[UI Mockups](./docs/)** | HTML prototypes of the web application interfaces. |

---

## 📏 Development Guidelines

To maintain the integrity of the codebase, all contributors must adhere to the following rules:

1. **Never hardcode statutory values.** All PF ceilings, PT slabs, and tax rates must be read from `config/statutory.php`.
2. **Respect the `LocationScope`.** Never use `DB::table()` or raw queries on tenant-scoped models. Always use Eloquent.
3. **Additive Migrations Only.** Never drop columns or change column types in existing migrations. Create a new migration for schema changes.
4. **Skinny Controllers, Fat Services.** Business logic belongs in `app/Services/`. Controllers should only handle HTTP concerns.
5. **No PII in Logs.** Never log Aadhaar numbers, PANs, or raw passwords. Use IDs or masked values.
6. **Form Requests for Validation.** Never validate input directly in the controller. Use `app/Http/Requests/`.

---

##  Contributing

1. Fork the repository.
2. Create your feature branch (`git checkout -b feature/amazing-feature`).
3. Write/update Pest tests for your changes.
4. Ensure the CI pipeline passes (`composer pint`, `phpstan`, `php artisan test`).
5. Commit your changes (`git commit -m 'feat: add amazing feature'`).
6. Push to the branch (`git push origin feature/amazing-feature`).
7. Open a Pull Request.

---

## 📄 License

Copyright © 2026 Plus91 Technologies Pvt Ltd. All rights reserved.
*This software is proprietary and confidential. Unauthorized copying, distribution, or use is strictly prohibited.*