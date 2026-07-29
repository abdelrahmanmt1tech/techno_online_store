# HR Lite

Branch: `feature/hr-lite`  
Phase: 11

## Scope

Lightweight HR for SMB tenants:

- Employees (optional link to `TenantUser`)
- Departments & job titles
- Attendance schedules & geofenced locations
- Check-in / check-out page
- Auto absence marking
- Simple deductions
- Monthly payroll lite + browser salary slip

**Out of scope:** OT, allowances, commissions, insurance/tax, loans, recruitment, biometric devices, mobile apps, continuous GPS tracking, leave balances, accounting/POS integration, fixed roles.

## Architecture

```text
Filament Tenant Resources + /app/hr/attendance Blade
        ↓
Thin Controllers / Filament Actions
        ↓
Actions/Services (Hr)
        ↓
Tenant DB tables (hr_*)
```

## Schedule resolution

1. Employee `attendance_schedule_id`
2. HR Settings default / schedule marked `is_default`

## Location resolution

1. Employee `attendance_location_id`
2. Active location for employee branch
3. HR Settings default location  
If none → check-in rejected.

## Geolocation

Browser sends lat/lon/accuracy. Backend recalculates Haversine distance (`GeolocationService`).  
Accept when `distance <= allowed_radius_meters` and accuracy within configured max.

**Limitation:** browser geolocation is not anti-spoofing; suitable for SMB practical control, not a biometric replacement.

## Attendance

One row per `employee_id + attendance_date`.  
Server time only. Statuses: present, late, absent, incomplete, day_off, manual.

Page: `/app/hr/attendance`  
Command: `php artisan hr:mark-absent` (hourly scheduler).

## Deductions

Absence: `none` | `daily_rate` | `fixed_amount`  
Late: `none` | `fixed_per_late_day` | `per_minute` (+ optional daily cap)  
Employee custom absence settings override tenant defaults.

## Payroll

`Net = max(0, base_salary - absence - late - manual)`  
Lifecycle: `draft → generated → approved → paid` (cancel before paid).  
Snapshots freeze salary/type at generate time. No GL / cash drawer posting.

Slip: `/app/hr/payroll-employees/{id}/slip` (browser print).

## Permissions

Keys only (no fixed HR roles). Admin creates roles and assigns keys via existing Roles UI + `tenants:sync-permissions`.

See `TenantPermissionsArray` group `hr.*`.

## Reports

Filament pages (permission `hr.reports.view`):

- `/app/hr-attendance-summary`
- `/app/hr-payroll-summary`

CSV/Excel export is not included (no new export package).

## Routes

| Route | Purpose |
|---|---|
| `/app/hr/attendance` | Employee check-in / check-out |
| `/app/hr/payroll-employees/{id}/slip` | Browser-printable salary slip |
| Filament HR resources under `/app/...` | Admin CRUD + payroll lifecycle |

## Deploy

```bash
php artisan tenants:sync-permissions --migrate
php artisan hr:mark-absent   # optional manual
```

Ensure Laravel scheduler/cron is running for hourly absence marking.
