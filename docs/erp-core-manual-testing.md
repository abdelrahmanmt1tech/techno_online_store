# ERP Core — Manual Testing (Tenant Filament `/app`)

1. Create **Branch** and **Warehouse** (optional link).
2. Create **Unit of Measure** (PCS) and **Inventory Item** (FIFO, track stock).
3. **Stock Receipt**: two lines same item — qty 10 cost 100, qty 5 cost 120 → Post.
4. **Stock Issue** qty 12 → Post; open FIFO layers / movements — expect remaining 3 @ 120.
5. **Transfer** to second warehouse → store product qty unchanged if item linked.
6. **Damage** / **Adjustment** out → FIFO consumed; adjustment in creates new layer.
7. Create **Supplier**, **Purchase Order** (approve) — balances unchanged.
8. **Goods Receipt** from PO (partial) → Post — ERP up; if commerce line, store qty up.
9. **Purchase Invoice** from GR → Record partial then full payment.
10. **Sale** with repeater: inventory + commerce product + manual line → Confirm.
11. **Create Invoice** (partial then remainder); confirm `order_id` if sale linked to order.
12. **Record Payment** on sales invoice.
13. **Sales Return** restock inventory with reason; commerce restock increases store qty; damaged does not.
14. Product with variants: receipt/sale must pick **variant**; parent qty unchanged.
15. Re-Post / Re-Confirm same document — no double stock/commerce movement.
16. Try edit posted document — should be view-locked; use Reverse where available.
17. **Invoice Print Settings**: set company AR/EN, logo, hide SKU → Save.
18. Open Sales Invoice → **Print** (new tab) → verify RTL/LTR, totals, toolbar hidden in print preview.
19. Change company name → reprint **old issued** invoice (snapshot) vs **new** invoice (live settings).
20. Print Purchase Invoice → supplier, PO/GR refs, paid/due.
21. Browser **Save as PDF** from print dialog.
