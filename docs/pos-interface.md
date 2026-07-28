# POS Interface (Blade + Vue)

Branch: `feature/erp-next-phase`  
Phase: 10

## Architecture

```text
Laravel Route (/app/pos)
  → Blade shell (CSRF + bootstrap JSON)
    → <div id="pos-app">
      → Vue 3 (Vite entry resources/js/pos/app.js)
        → Axios (session cookies + CSRF)
          → Thin Controllers + Form Requests
            → PosTerminalService
              → UnifiedSalesEngine / CashierSessionService /
                PosRegisterGuard / CashMovementService / ShiftReportService
```

**Not used:** SPA, Vue Router, Sanctum, Pinia, business logic in Vue/controllers.

Store checkout remains unwired to `UnifiedSalesEngine`.

## Routes

| Method | Path | Name | Purpose |
|---|---|---|---|
| GET | `/app/pos` | `filament.tenant.pos` | Blade + Vue shell |
| GET | `/app/pos/receipt/{sale}` | `filament.tenant.pos.receipt` | Printable receipt |
| GET | `/app/pos/api/bootstrap` | `…pos.api.bootstrap` | Initial state |
| GET | `/app/pos/api/products` | `…pos.api.products` | Paginated catalog |
| GET | `/app/pos/api/barcode` | `…pos.api.barcode` | Barcode lookup |
| GET/POST | `/app/pos/api/customers` | search / quick create |
| POST | `/app/pos/api/session/open` | open shift |
| GET | `/app/pos/api/session/status` | session status |
| POST | `/app/pos/api/session/close` | close shift |
| GET | `/app/pos/api/session/summary` | shift summary |
| POST | `/app/pos/api/cash-in` / `cash-out` | cash movements |
| POST | `/app/pos/api/checkout` | complete sale |
| POST | `/app/pos/api/suspend` | suspend cart |
| GET | `/app/pos/api/suspended` | list suspended |
| POST | `/app/pos/api/suspended/{sale}/resume` | resume |
| POST | `/app/pos/api/suspended/{sale}/cancel` | cancel / abandon |

Auth: Filament tenant session (`authGuard('tenant')`) + CSRF. No tokens.

## Vue structure

```text
resources/js/pos/
  app.js          # mount #pos-app
  api.js          # Axios client
  App.vue         # screen state, modals, shortcuts
resources/css/pos.css
resources/views/pos/app.blade.php
resources/views/pos/receipt.blade.php
```

Vite inputs (isolated from store assets): `resources/css/pos.css`, `resources/js/pos/app.js`.

## Session flow

1. Open `/app/pos` (or Filament nav **POS Terminal**).
2. If no `opened` session → Open Shift modal (opening balance + notes).
3. `POST session/open` → enter selling UI.
4. Closing / closed / cancelled sessions cannot sell (`PosRegisterGuard`).

## Checkout flow

1. Build cart in Vue (display totals only — not trusted).
2. Payment modal (cash/card/transfer/other; **split payments supported** by posting multiple tenders).
3. `POST checkout` with items + payments + `idempotency_key`.
4. Backend recalculates prices from catalog, guards session, completes via `UnifiedSalesEngine`, allocates payments (overpay → change).
5. UI clears cart, shows receipt number + change, opens receipt preview URL.

## Suspend flow

1. `POST suspend` → draft sale marked suspended in DB (not browser-only).
2. List via `GET suspended`.
3. Resume → engine unsuspends; UI reconstitutes cart; `cancel` abandons the draft so a new checkout creates a fresh sale.
4. Cancel suspended → cancels sale.

## Closing flow

1. Load shift summary (expected by tender, sales count, refunds, net cash).
2. Enter actual cash/card/transfer/other + notes.
3. `POST session/close` → `CloseCashierSessionAction`.
4. Selling blocked until a new shift is opened.

## Receipt flow

`GET /app/pos/receipt/{sale}` Blade view → browser print. Independent of ERP invoice print settings.

## Keyboard shortcuts

| Key | Action |
|---|---|
| F2 | Focus product search |
| F4 | Customer modal |
| F8 | Payment |
| F9 | Suspend |
| Esc | Close modal |

Barcode scanners act as keyboard input (buffer + Enter outside inputs).

## Error handling

Validation / guard failures return HTTP 422 with translated `commerce.validation.*` messages (EN/AR). Axios surfaces `message` or first validation error.

Covered cases: no open session, invalid register, insufficient stock, invalid qty/price/discount, unavailable product/variant, payment mismatch, session closed, suspended expired, unauthorized user, double-submit guarded client-side via `checkoutBusy` + idempotency key.

## Store isolation

- No changes to Store checkout controllers/APIs/JS.
- POS sells through commerce sale lines + `UnifiedSalesEngine` only.
- POS Vite entry is separate from `resources/js/app.js`.
