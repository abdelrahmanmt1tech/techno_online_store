<?php

/**
 * POS Runtime & Shift Operations
 *
 * Branch: feature/erp-next-phase
 * Phase: 9
 * Date: 2026-07-28
 */

## Scope

Backend runtime for POS shifts — **no Vue / Blade POS UI**.

Store checkout is **not** wired to `UnifiedSalesEngine` in this phase.

## Session lifecycle

```text
Opening ──► Opened ──► Closing ──► Closed
   │           │
   └───────────┴──► Cancelled
```

| From | Allowed next |
|---|---|
| `opening` | `opened`, `cancelled` |
| `opened` | `closing`, `cancelled` |
| `closing` | `closed`, `opened` (abort close) |
| `closed` / `cancelled` | terminal |

Rules:

- At most **one** non-terminal session per **register**.
- At most **one** non-terminal session per **user**.
- POS selling requires status **`opened`** (`PosRegisterGuard`).
- Cancel blocked if the session has confirmed (non-draft/cancelled) sales.

Actions:

- `App\Actions\Pos\OpenCashierSessionAction`
- `App\Actions\Pos\CloseCashierSessionAction` (expected tender totals + actual cash/card/transfer/other)
- `App\Actions\Pos\CancelCashierSessionAction`
- Facade: `CashierSessionService`

## Cash flow (immutable ledger)

Table: `cash_movements` (extended).

Types: `opening`, `cash_in`, `cash_out`, `sale_payment`, `refund`, `adjustment`, `closing`, `transfer` (+ legacy aliases).

Rules:

- Create only via `CashMovementService::record()`.
- Model blocks `update` / `delete` (`LogicException`).
- Corrections: `CashMovementService::reverse()` creates an opposite immutable row (`is_reversal`, `reverses_movement_id`).

POS invoice payments through `UnifiedSalesEngine::recordPayment()` also post `sale_payment` movements when the sale is POS-linked.

## Register Guard

`App\Services\Pos\PosRegisterGuard`

Asserts authenticated tenant user, active register, active drawer, operational (`opened`) session owned by that user on that register.

Throws `PosRegisterGuardException` (mapped to validation errors in the sales engine).

## Receipt strategy

`PosReceiptNumberService` + `pos_receipt_sequences`.

Default strategy: **Branch + Register + Date + daily sequence**

Example: `BR001-FR1-20260728-0001`

Also supports `per_register` and `global` (via document sequences).

Assigned to `sales.receipt_number` when creating a POS draft via the engine.

## Shift reports (services only)

`ShiftReportService`:

- `xReport($session)` — mid-shift snapshot
- `zReport($session)` — end-of-shift style payload
- `shiftSummary($session)` — same metrics package

Metrics include sales count/amount, refunds, payments by method, opening/expected/actual/difference, net cash, taxes, discounts, average sale, cancelled & suspended counts.

## Suspend sales

Via `UnifiedSalesEngine`:

- `suspend` — draft only; optional `suspended_until` from `pos_settings.suspend_expires_minutes`
- `resume` — rejects expired holds
- `cancelSuspended` — clears hold and sets sale status `cancelled`

## UnifiedSalesEngine orchestrator

Controllers / Filament for sales workflows must call the engine.

The engine:

1. Runs `PosRegisterGuard` for POS channel
2. Allocates receipt numbers
3. Orchestrates confirm / invoice / payment / return / suspend
4. Posts POS cash movements on payment
5. Delegates FIFO/invoice math to existing ERP Actions

## Filament admin

Read-only **Cash Movements** resource under POS nav. Sessions remain list/view; settings singleton updated for receipt strategy + suspend expiry.

## Tests

`tests/Feature/Pos/PosRuntimeTest.php` + existing `CommerceCoreTest` / ERP suite.

## UI

Phase 10 adds the Blade + Vue terminal — see [`docs/pos-interface.md`](docs/pos-interface.md) (`/app/pos`).
