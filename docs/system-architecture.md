# HRIS System Architecture

## Overview

This is a **Human Resource Information System (HRIS)** targeting Philippine-based companies. It manages the full employee lifecycle — recruitment, attendance, leave, payroll, and statutory compliance — across multiple companies through a licensed, modular platform.

---

## Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Language | PHP | 8.3.8 |
| Framework | Laravel | v12 |
| Frontend Bridge | Inertia.js | v2 |
| Authentication | Laravel Fortify | v1 |
| Frontend | Vue 3 (Composition API) | v3 |
| Styling | Tailwind CSS | v4 |
| Type-safe Routes | Laravel Wayfinder | v0 |
| PDF Generation | DomPDF | — |
| Testing | PHPUnit | v11 |
| Build Tool | Vite | — |
| Code Style | Laravel Pint, ESLint, Prettier | — |

---

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                      Browser (SPA)                       │
│           Vue 3 + Inertia.js + Tailwind CSS v4          │
└──────────────────────────┬──────────────────────────────┘
                           │ HTTP (Inertia protocol)
┌──────────────────────────▼──────────────────────────────┐
│                   Laravel 12 Backend                     │
│                                                          │
│  ┌──────────┐  ┌────────────┐  ┌──────────┐  ┌───────┐ │
│  │   Core   │  │Timekeeping │  │ Payroll  │  │License│ │
│  │  Module  │  │   Module   │  │  Module  │  │Module │ │
│  └──────────┘  └────────────┘  └──────────┘  └───────┘ │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │  Laravel Services: Fortify · Wayfinder · Pint    │   │
│  └──────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────┘
                           │ Eloquent ORM
┌──────────────────────────▼──────────────────────────────┐
│                      Database (SQLite / MySQL)            │
└─────────────────────────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│              Biometric Terminal (Alpeta)                  │
│         BiometricTerminal · AlpetaLog · Sync Jobs        │
└─────────────────────────────────────────────────────────┘
```

---

## Modular Structure

The application is organized into **self-contained feature modules** under `app/Modules/`. Each module owns its Models, Controllers, Form Requests, and Routes.

```
app/Modules/
├── Core/
│   ├── Controllers/        DashboardController, EmployeesController, NotificationsController
│   ├── Models/             Company, Employee, Department, Position, Role, Permission,
│   │                       AuditLogs, License, Module, LicenseModule
│   ├── Requests/
│   └── routes/web.php
│
├── Timekeeping/
│   ├── Controllers/        AttendanceController, LeaveController, DtrController,
│   │                       ShiftTemplatesController, CalendarController,
│   │                       OvertimeController, ReportController
│   ├── Models/             AttendanceRecord, LeaveRequest, LeaveBalance, LeaveType,
│   │                       ShiftTemplate, EmployeeSchedule, OvertimeRecord,
│   │                       ShiftSwapRequest, WorkPolicy, BiometricTerminal,
│   │                       BiometricSyncLog, AlpetaLog
│   ├── Requests/
│   └── routes/web.php
│
├── Payroll/
│   ├── Controllers/        PayrollController, PayrollPeriodController, PayslipController,
│   │                       PayrollSettingsController, HolidayController,
│   │                       AllowanceTypeController, LoanController, LoanTypeController,
│   │                       EmployeePayslipController
│   ├── Models/             PayrollPeriod, PayrollItem, PayrollEarning, PayrollDeduction,
│   │                       PayrollSetting, Holiday, AllowanceType, EmployeeAllowance,
│   │                       Loan, LoanType, ContributionBracket
│   ├── Requests/
│   └── routes/web.php
│
└── License/
    ├── Controllers/        LicenseController
    ├── Models/             License, Module, LicenseModule
    └── routes/web.php
```

---

## Data Model

### Core Domain

```
Company ──< Department ──< Employee >── Position
                               │
                    ┌──────────┼──────────┐
                    │          │          │
               AttendanceRecord  LeaveRequest  PayrollItem
                                 LeaveBalance
```

### Employee

| Field | Type | Notes |
|---|---|---|
| employment_status | enum | Active, Resigned, On Leave, Suspended |
| employment_type | enum | Full-time, Part-time, Contract, Probationary |
| salary_type | enum | Hourly, Daily, Monthly |
| basic_salary | decimal | Base pay |

### Attendance

```
AttendanceRecord
├── clock_in         (morning start)
├── morning_out      (break start)
├── afternoon_in     (break end)
├── clock_out        (end of day)
├── total_hours      (computed)
├── status           Present | Late | Absent | Half Day
├── source           biometric | manual
└── alpeta_log_id    FK → AlpetaLog (biometric sync)
```

### Payroll Pipeline

```
PayrollPeriod (draft → processing → finalized → closed)
    └── PayrollItem (one per employee per period)
            ├── PayrollEarning[]
            │       basic_salary, allowances, overtime, holiday_pay
            │       night_differential, (taxable flag)
            └── PayrollDeduction[]
                    SSS, PhilHealth, PagIBIG, withholding_tax, loans
```

### Shift & Schedule

```
ShiftTemplate  ──>  EmployeeSchedule  ──>  Employee
(work_start,         (effective_date)
 work_end,
 break_start,
 break_end,
 work_days[])
```

---

## Authentication & Authorization

### Authentication
- Handled by **Laravel Fortify** (v1)
- Supports: email/password, Two-Factor Authentication (TOTP), email verification
- Sessions managed by Laravel's standard session guard

### Authorization
- **RBAC** via `Role` and `Permission` models (many-to-many)
- `User` ↔ `Role` ↔ `Permission`
- Route-level protection: `auth` + `module.access:{module}` middleware
- `module.access` checks if the company's license has the requested module enabled

### Module Feature Gating
```
License ──< LicenseModule >── Module
```
Each company has a `License`. Modules (Core, Timekeeping, Payroll) are attached to licenses. The `module.access` middleware enforces this at the route level.

---

## Routes Map

| Area | Prefix | Module |
|---|---|---|
| Dashboard | `/` | Core |
| Employees | `/employees` | Core |
| Timekeeping | `/timekeeping` | Timekeeping |
| Daily Time Record | `/timekeeping/dtr` | Timekeeping |
| Leave Types | `/hr-settings/leave-types` | Timekeeping |
| Shift Templates | `/hr-settings/shift-templates` | Timekeeping |
| Payroll | `/payroll` | Payroll |
| Payroll Periods | `/payroll/periods` | Payroll |
| My Payslips | `/payroll/my-payslips` | Payroll |
| Loans | `/loans` | Payroll |
| Payroll Settings | `/hr-settings/payroll` | Payroll |
| Holidays | `/hr-settings/holidays` | Payroll |
| Allowance Types | `/hr-settings/allowance-types` | Payroll |
| Loan Types | `/hr-settings/loan-types` | Payroll |
| Licenses | `/license/licenses` | License |
| Notifications (API) | `/api/core/notifications` | Core |
| Calendar (API) | `/api/dashboard/calendar` | Timekeeping |
| User Settings | `/settings/*` | Framework |

---

## Frontend Structure

```
resources/js/
├── pages/                    # Inertia page components
│   ├── Dashboard.vue
│   ├── auth/                 Login, Register, 2FA, Verify, Password
│   ├── Employees/            Employees.vue, EmployeeForm.vue, EmployeeDetail.vue
│   ├── Timekeeping/          Timekeeping.vue, Dtr.vue
│   ├── Payroll/              Payroll.vue, Periods.vue, PeriodShow.vue,
│   │                         MyPayslips.vue, Payslip.vue,
│   │                         Settings/PayrollSettings.vue, Settings/Holidays.vue,
│   │                         Loans.vue
│   ├── HRSettings/           LeaveTypes.vue, ShiftTemplates.vue,
│   │                         AllowanceTypes.vue, LoanTypes.vue
│   ├── Licenses/             Licenses.vue, LicenseDetail.vue
│   └── settings/             Profile.vue, Password.vue, TwoFactor.vue, Appearance.vue
│
├── components/
│   ├── ui/                   Shadcn-style design system (Button, Card, Dialog,
│   │                         Table, Input, Badge, Sheet, Sidebar, Tooltip, ...)
│   ├── Dashboard/            Dashboard-specific components
│   └── Timekeeping/          TimekeepingReports.vue and related components
│
├── layouts/
│   ├── app.vue               Main authenticated layout (sidebar + nav)
│   ├── auth.vue              Login/register layout
│   └── settings.vue          Settings sub-layout
│
├── composables/
│   ├── useModuleAccess.ts    Check if a module is licensed/enabled
│   └── useTimekeeping.ts     Timekeeping data and actions
│
├── actions/                  Wayfinder-generated type-safe controller bindings
│   └── App/Modules/*/Controllers/
│
└── routes/                   Named route definitions (Wayfinder)
```

---

## Key Business Logic

### Payroll Calculation Flow
1. A `PayrollPeriod` is created (start/end date, cutoff dates).
2. Admin runs the period → system generates a `PayrollItem` per active employee.
3. Each `PayrollItem` gets `PayrollEarning` records (basic salary, allowances, OT, holiday pay, night differential).
4. Each `PayrollItem` gets `PayrollDeduction` records (SSS, PhilHealth, PagIBIG via `ContributionBracket`, withholding tax, loan deductions).
5. Period is finalized → payslips become available for download (PDF via DomPDF).

### Attendance Flow
1. Employee clocks in/out via web UI or biometric terminal.
2. Biometric data syncs from Alpeta terminal → `AlpetaLog` → mapped to `AttendanceRecord`.
3. DTR (Daily Time Record) is computed from attendance records for a given period.
4. Late arrivals, absences, half days are flagged based on `ShiftTemplate` settings.

### Leave Flow
1. Employee submits `LeaveRequest` against a `LeaveType`.
2. HR approves/rejects → `LeaveBalance` is decremented on approval.
3. Approved leaves are reflected in the attendance calendar.

### Overtime Flow
1. Employee submits `OvertimeRecord` (pre or post).
2. HR approves → OT hours feed into payroll as `PayrollEarning` with the applicable OT rate from `WorkPolicy`.

---

## Philippine Statutory Compliance

| Contribution | Model | Notes |
|---|---|---|
| SSS | `ContributionBracket` | Salary-bracket based employee + employer share |
| PhilHealth | `ContributionBracket` | Percentage-based, shared equally |
| PagIBIG (HDMF) | `ContributionBracket` | Fixed/percentage with ceiling |
| Withholding Tax | `PayrollSetting` | Progressive tax table |
| Night Differential | `PayrollSetting` | 10% of hourly rate for 10PM–6AM hours |
| Holiday Pay | `Holiday` + `WorkPolicy` | Regular/special holiday rates configurable |
| Overtime | `WorkPolicy` | Regular OT, rest day OT, holiday OT rates |

---

## Biometric Integration

```
Alpeta Terminal
      │  (HTTP sync)
      ▼
BiometricTerminal  →  BiometricSyncLog
      │
      ▼
AlpetaLog  →  AttendanceRecord (mapped by employee_id + date)
```

- `BiometricTerminal` stores terminal connection credentials.
- `BiometricSyncLog` records each sync attempt (success/failure, records synced).
- `AlpetaLog` stores raw biometric events before they are mapped to `AttendanceRecord`.

---

## Testing Strategy

| Type | Location | Tool |
|---|---|---|
| Feature tests | `tests/Feature/` | PHPUnit v11 |
| Unit tests | `tests/Unit/` | PHPUnit v11 |

Tests are organized by module mirror (e.g., `tests/Feature/Timekeeping/`). Every controller action and business-logic path should have corresponding feature test coverage covering happy path, failure path, and edge cases.

---

## Development Commands

```bash
# Start development servers
composer run dev         # Starts Laravel + Vite concurrently

# Frontend only
npm run dev              # Vite dev server
npm run build            # Production build

# Backend
php artisan test                                  # Run all tests
php artisan test tests/Feature/Timekeeping/       # Run module tests
php artisan test --filter=testName                # Run specific test
vendor/bin/pint --dirty                           # Fix PHP code style
php artisan wayfinder:generate                    # Regenerate type-safe actions

# Database
php artisan migrate
php artisan db:seed
```
