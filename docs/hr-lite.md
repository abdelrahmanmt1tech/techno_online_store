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

### Overnight shifts

**Not supported.** If a schedule day has `end_time <= start_time`, that day is treated as **non-working** and check-in is rejected. Configure same-day windows only (e.g. `09:00`–`17:00`).

## Location resolution

1. Employee `attendance_location_id`
2. Active location for employee branch
3. HR Settings default location  
If none → check-in rejected.

## Geolocation

Browser sends `latitude`, `longitude`, and `accuracy` only. Backend resolves employee from the authenticated user, recalculates Haversine distance (`GeolocationService`), and uses **server time** for punches.

### GPS accuracy

Field name: **`maximum_accuracy_meters`** on `hr_attendance_locations` (and `default_maximum_accuracy_meters` in HR settings).

**Lower accuracy value = better GPS fix.** The limit is the **maximum allowed** inaccuracy in meters.

| Setting | Received accuracy | Result |
|---|---|---|
| 100 m | 50 m | Accept |
| 100 m | 100 m | Accept (boundary) |
| 100 m | 101 m | Reject |
| `null` | any positive | No accuracy gate |

Comparison: `received_accuracy <= maximum_accuracy_meters`

- `null` limit → no accuracy constraint.
- Accuracy must be a **positive number** when a limit is enforced (`min:1` on punch requests).
- Frontend hints are not trusted; the backend decides.

**Browser limits:** Geolocation accuracy varies by device, OS permission, and environment (indoor/outdoor). Typical mobile accuracy is tens of meters; desktop/Wi‑Fi may be worse. This module is practical SMB control, not anti-spoofing.

Accept punch when `distance <= allowed_radius_meters` and accuracy passes the rule above.

## Attendance

One row per `employee_id + attendance_date`.  
Server time only (`attendance_date` follows the application timezone). Statuses: `present`, `late`, `absent`, `incomplete`, `day_off`, `manual`.

Page: `/app/hr/attendance`  
Endpoints (session auth + CSRF, tenant domain):

| Method | Path | Permission |
|---|---|---|
| GET | `/app/hr/attendance` | `hr.attendance.check_in` **or** `hr.attendance.check_out` |
| GET | `/app/hr/attendance/status` | same as page |
| GET | `/app/hr/attendance/distance` | same as page |
| POST | `/app/hr/attendance/check-in` | `hr.attendance.check_in` |
| POST | `/app/hr/attendance/check-out` | `hr.attendance.check_out` |

Rules:

- Employee is resolved from **auth user**; `employee_id` is never accepted from the client.
- User must belong to the current tenant, be linked to an active `HrEmployee`, and hold the permission for the action.
- Roles are **dynamic** (Spatie keys via Roles UI); no built-in HR roles.

## Auto absence (`hr:mark-absent`)

Registered hourly in `routes/console.php`:

```php
Schedule::command('hr:mark-absent')->hourly();
```

Runs from **central** cron context (`php artisan schedule:run` on the central app):

```text
Central command
  → iterate Tenant::active()
  → tenant->run() (initialize tenancy)
  → MarkAbsentEmployeesAction
  → catch/log per-tenant failures (tenant_id in logs)
  → end tenancy
  → continue with next tenant
```

Behavior:

- **Idempotent** — does not create duplicate absent rows.
- Waits until `scheduled_end + 30 minutes` before marking absent (no early absent).
- Skips: inactive employees, non-working days, employees with check-in, employees without a valid schedule.
- One tenant failure does not block others.

Manual run: `php artisan hr:mark-absent` or `php artisan hr:mark-absent --date=2026-07-29`

## Deductions

Absence: `none` | `daily_rate` | `fixed_amount`  
Late: `none` | `fixed_per_late_day` | `per_minute` (+ optional daily cap)  
Employee custom absence settings override tenant defaults.

### Payable days (attendance → payroll)

| Status | Counted as payable day |
|---|---|
| `present` | Yes |
| `late` | Yes (late deduction applied separately) |
| `manual` | Yes (admin-entered attendance) |
| `incomplete` | No (unless adjusted by admin) |
| `absent` | No |
| `day_off` | No (unpaid for daily employees in this lite model) |

## Payroll

Snapshots freeze `base_salary` and `salary_type` at generate time. `calculation_details` documents rates, payable days, and deduction breakdown. No GL / cash drawer posting.

### Monthly employee

`base_salary` = full monthly salary.

```text
gross = base_salary_snapshot
net = max(0, gross - absence_deduction - late_deduction - manual_deduction)
```

### Daily employee

`base_salary` = **daily rate** (one day's wage), not a monthly total.

```text
payable_days = present_days (present + late + manual)
gross = base_salary_snapshot × payable_days
absence_deduction = 0   # absent days already excluded from payable_days
net = max(0, gross - late_deduction - manual_deduction)
```

**No double absence deduction** for daily employees: absent days reduce `payable_days` only; `absence_deduction` is forced to `0.00`.

Lifecycle: `draft → generated → approved → paid` (cancel before paid).

Slip: `/app/hr/payroll-employees/{id}/slip` (browser print).

## Permissions

Keys only (no fixed HR roles). Admin creates roles and assigns keys via existing Roles UI + `tenants:sync-permissions`.

See `TenantPermissionsArray` group `hr.*`.

Attendance page keys: `hr.attendance.check_in`, `hr.attendance.check_out` (plus admin keys `hr.attendance.view`, `hr.attendance.manage`, `hr.attendance.adjust`).

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
php artisan hr:mark-absent   # optional manual smoke test
```

Ensure Laravel scheduler/cron runs on the **central** application:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

The hourly `hr:mark-absent` job processes all active tenants automatically; no per-tenant cron is required.

## Attendance page Check-In fix (Phase 12)

### Root cause

The previous Blade UI called `watchPosition` on page load and only punched when `lastPos` was already set. If geolocation was slow, denied, unavailable (common on plain HTTP), or timed out, Check In appeared broken: the button fired but immediately showed a location error without acquiring a fresh fix. Status labels after punch also showed raw enum keys (`late`) instead of translated labels. The `/status` payload previously exposed workplace lat/lon.

### Fix

- Acquire location with `getCurrentPosition` (high accuracy, short `maximumAge`) on load **and** again on button press if needed.
- No continuous `watchPosition` polling.
- Disable buttons + spinner while punching; confirm on check-out.
- Backend responses include `status_label`.
- `/status` and `/distance` never return workplace coordinates.
- Clear UI states for not-linked / inactive / day-off / incomplete settings.
- Translations for browser geolocation error codes + HTTPS hint.

### Flow

1. User opens `/app/hr/attendance` (session + CSRF + Spatie permission).
2. Page resolves employee from auth user only.
3. Browser requests geolocation; distance hint via `/distance` (optional UX).
4. On Check In/Out click → ensure coords → POST lat/lon/accuracy.
5. Backend validates permission, schedule, geofence, accuracy, duplicates; stamps **server time**.
6. UI updates from JSON (`status_label`, times, late/worked minutes).

### Requirements

- Prefer **HTTPS** (or secure context) for geolocation.
- Permissions: `hr.attendance.check_in` / `hr.attendance.check_out`.
- Linked active `HrEmployee`, working day, schedule + location configured.

### Manual QA notes

- Backend punch paths covered by Feature tests with mocked coordinates.
- Browser GPS depends on device/OS permissions; do not claim real GPS verification unless tested on a secure tenant domain with permission granted.
