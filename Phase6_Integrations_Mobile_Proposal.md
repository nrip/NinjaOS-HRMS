# Phase 6: Integrations & Mobile — Implementation Proposal

This proposal outlines the strategy for Phase 6, the final phase of NexusOS HRMS. This phase focuses on production readiness, robust mock integrations, and the React Native mobile app scaffold.

## 1. Mock External Integrations Strategy

To ensure seamless transition to production APIs, all mock services will implement interfaces bound via Laravel's Service Container. This allows swapping the mock implementation for a real HTTP client implementation by simply changing the binding in `AppServiceProvider`.

### 1.1 WhatsApp Business API Mock
- **Interface:** `WhatsAppServiceInterface`
- **Mock Implementation:** `MockWhatsAppService`
- **Job:** `SendWhatsAppMessageJob`
- **Behavior:** Accepts phone number and template data. The mock service will format the payload as JSON and write it to `storage/logs/whatsapp-mock.log` using a dedicated Monolog channel.

### 1.2 Tally/Zoho Accounting Mock
- **Interface:** `AccountingIntegrationInterface`
- **Mock Implementation:** `MockAccountingService`
- **Job:** `SyncPayrollToAccountingJob`
- **Behavior:** Triggered upon payroll finalization. Generates a standard double-entry accounting payload (XML for Tally, JSON for Zoho) containing salary expenses and statutory liabilities. Payload is logged to `storage/logs/accounting-mock.log`.

### 1.3 Banking (HDFC) Mock
*Note: We built `BankTransferFileService` in Phase 4. We will refactor it to ensure it perfectly matches the mandated HDFC NEFT/RTGS CSV format and store it securely.*
- **Storage:** `storage/app/bank-files/` (local disk, protected from public access).
- **HDFC CSV Column Structure:**
  1. `Transaction Type` (NEFT/RTGS/FT)
  2. `Debit Account No` (Company Account)
  3. `Beneficiary Account No`
  4. `Beneficiary Name`
  5. `Amount`
  6. `Beneficiary IFSC`
  7. `Value Date` (DD/MM/YYYY)
  8. `Customer Reference No` (e.g., `SAL-JUL-2026-EMP001`)

## 2. React Native Mobile App (Expo)

The mobile app will be scaffolded in the `mobile/` directory at the project root (outside the Laravel `nexusos/` directory to keep repositories clean, or inside if preferred for mono-repo).

### 2.1 Technology Stack
- **Framework:** React Native + Expo (Managed Workflow)
- **Navigation:** React Navigation v6 (Stack + Bottom Tabs)
- **State/Data:** React Query (for API caching) + Axios
- **Styling:** NativeWind (Tailwind CSS for React Native)

### 2.2 Secure Token Storage
- **Library:** `expo-secure-store`
- **Mechanism:** Upon successful login, the Laravel Sanctum token is encrypted and stored in the device's secure keychain/keystore. It is retrieved on app launch to hydrate the auth state and injected into Axios interceptors as the `Authorization: Bearer` header.

### 2.3 Navigation Structure
- **AuthStack**
  - `LoginScreen` (Email/Password → Sanctum Token)
- **MainTabs** (Bottom Tab Navigator)
  - `HomeTab` → Dashboard (Quick stats, upcoming holidays)
  - `AttendanceTab` → Punch In/Out UI (requests device location, displays mock map radius)
  - `LeaveTab` → Leave Balances List → Apply Leave Form Modal
  - `PayrollTab` → Payslip List → Payslip PDF Viewer (using `expo-linking` or `react-native-pdf` to open the API URL)

## 3. Security & Performance Prep

### 3.1 Rate Limiting
- Implement `throttle:api` (60 requests/minute) on standard API routes.
- Implement stricter `throttle:auth` (5 requests/minute) on login endpoints to prevent brute force.

### 3.2 API Resources
- Create Laravel API Resources (`EmployeeResource`, `LeaveBalanceResource`, `PayslipResource`) to ensure consistent JSON shaping.
- Explicitly exclude sensitive PII (e.g., Aadhar number, PAN) from general responses unless specifically requested by authorized roles.

### 3.3 Backups
- Install and configure `spatie/laravel-backup`.
- Configure `config/backup.php` to backup the DB and `storage/app` to a local disk named `backups` (simulating an S3 bucket).
- Add `backup:run` and `backup:clean` to the Laravel Scheduler.

## 4. File List & Execution Order

1. **Tests:** Write the 3 mandated tests (`Phase6IntegrationsTest.php`).
2. **API Resources & Routes:** Build the API Resources, configure rate limiting in `RouteServiceProvider`, and expose the mobile API routes in `routes/api.php`.
3. **Mock Integrations:** Build Interfaces, Mock Services, Jobs, and custom log channels. Refactor the Bank file generator.
4. **Security/Backups:** Install `spatie/laravel-backup` and configure the scheduler.
5. **Mobile Scaffold:** Initialize the Expo app in `nexusos/mobile/`, set up React Navigation, Axios, and `expo-secure-store`, and build the 4 core screens.
6. **Final Verification:** Run the full test suite and verify Expo build.

Please approve this proposal or request adjustments before I begin writing the tests.
