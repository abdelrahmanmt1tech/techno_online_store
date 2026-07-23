# ERP Invoice Printing

**Branch:** `feature/erp-invoice-printing` (from `feature/erp-core-fifo`)  
**Approach:** Browser print-ready HTML (Save as PDF from the browser). No DomPDF/mPDF.

## Discovery

See earlier section in git history. Key points: invoices issue as `issued` via Actions; no PDF libs; tenant media via `storage/tenant{id}/…`.

## Schema

### `invoice_print_settings` (singleton per tenant)

Company identity, legal numbers, logo/stamp/signature paths, colors, paper size/orientation, locale, translatable titles/texts, `display_options` JSON, `layout_options` JSON.

### Snapshot columns

- `sales_invoices.print_settings_snapshot` (JSON nullable)
- `purchase_invoices.print_settings_snapshot` (JSON nullable)

## Snapshot rules

| Status | Behavior |
|---|---|
| Draft | Live settings + Draft watermark; no snapshot required |
| Issued / paid / … | Snapshot stored at **issue** (`CreateSalesInvoiceAction` / `CreatePurchaseInvoiceAction`) and ensured on **first print** if empty (`InvoicePrintDataBuilder::ensureSnapshot`) |
| After snapshot | Settings changes do **not** alter historical prints |

## Services

- `App\Services\Erp\InvoicePrintSettingsService` — defaults, getOrCreate, merge display options, locale, buildSnapshot
- `App\Services\Erp\InvoicePrintDataBuilder` — sales/purchase view DTOs
- `App\Support\Erp\TenantMediaUrl` — `asset('storage/tenant'.tenant('id').'/'.$path)`

## Routes (Filament tenant panel, authenticated)

- `GET /app/erp/sales-invoices/{salesInvoice}/print` → `filament.tenant.erp.sales-invoices.print`
- `GET /app/erp/purchase-invoices/{purchaseInvoice}/print` → `filament.tenant.erp.purchase-invoices.print`

Query: `?locale=ar|en`, `?autoprint=1`

## Filament

- `InvoicePrintSettingResource` singleton manage page (no create/delete)
- Print actions on sales/purchase invoice tables + view pages (`ErpPrintActions`)

## Views

`resources/views/erp/invoices/{layout,sales,purchase}.blade.php` + `partials/*`  
CSS: `resources/css/erp-invoice-print.css` (inlined; no Vite on print page)

## Out of scope

Server PDF, ZATCA/ETA e-invoicing, email/WhatsApp send, public customer links, branch-level setting overrides, thermal POS.

## Manual check

1. ERP Settings → Invoice Print Settings → save logo & company  
2. Print sales/purchase invoice → browser Print / Save as PDF  
3. Change company name → old issued invoice keeps snapshot; new invoice uses new name  
4. Arabic `dir=rtl`, English `dir=ltr`
