## Tenant Sidebar Navigation

This document explains how the Tenant panel sidebar navigation is customized and where to change it safely.

### Goal

The Tenant panel uses a centrally defined navigation layout so the sidebar order matches the requested business structure instead of relying on scattered per-resource navigation groups and sort values.

The current structure follows the requested order from `NavigationReqSort.ini`:

1. Sales & POS
2. Purchases & Suppliers
3. Inventory Management
4. Finance & Accounting
5. CRM & Marketing
6. E-Commerce & Website
7. Human Resources
8. Settings & Admin

The dashboard remains at the top as a standalone item.

### Files involved

- `app/Providers/Filament/TenantPanelProvider.php`
- `app/Support/Filament/TenantNavigationBuilder.php`
- `lang/ar/tenant_navigation.php`
- `lang/en/tenant_navigation.php`

### How it works

`TenantPanelProvider` now uses a custom Filament `navigation()` builder instead of relying only on auto-discovered item order:

- all Tenant and CRM resources/pages are still discovered normally
- the final sidebar order is assembled centrally by `TenantNavigationBuilder`
- manual links such as the POS terminal and employee attendance page are injected from the builder

This keeps the navigation logic in one place without editing every resource class.

### Collapsed groups by default

All sidebar groups are registered with `->collapsed()` so the user opens each section manually.

Important Filament 5 note:

- a navigation group cannot have an icon if its items already have icons
- because of that, the custom groups are intentionally registered without group icons

### Label overrides

Some sidebar labels are overridden centrally to match the requested terminology, for example:

- POS terminal
- Supplier directory
- Inventory items & products
- Stock count & adjustments
- Cost centers
- Customer / supplier statement

These labels are stored in:

- `lang/ar/tenant_navigation.php`
- `lang/en/tenant_navigation.php`

### Where to change the order later

If you want to reorder items or move an item between sections, edit:

- `TenantNavigationBuilder::groupEntries()`

If you want to rename a group or a custom label, edit:

- `lang/ar/tenant_navigation.php`
- `lang/en/tenant_navigation.php`

### Module gating

Sidebar entries are filtered by sellable modules before render (`TenantModuleGate` / `tenant_module_any_enabled()`).

Examples:

- Products + categories + brands → `store` **or** `pos`
- Storefront ecommerce (orders, coupons, CMS…) → `store`
- POS terminal + ERP inventory/purchases/sales → `pos`
- Suppliers → `pos` **or** `crm`
- CRM / messaging → `crm`
- Finance & accounting → `accounting`
- HR resources → `hr`
- My Subscriptions (`MySubscriptionsPage`) → always under Settings (no module gate)

See [`docs/tenant-modules.md`](tenant-modules.md) for the full ownership map.
