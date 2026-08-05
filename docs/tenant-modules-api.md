# Tenant storefront — modules API (for frontend)

Base path (per tenant domain):

```
https://{tenant-domain}/api/tenant
```

Tenancy is resolved by domain. No auth required for module status.

---

## 1) Check store module status (recommended bootstrap call)

### `GET /api/tenant/modules/store`

Call this **before** loading the storefront. If `enabled` is `false`, show an “store unavailable / subscription expired” screen and do **not** call products/cart/checkout APIs.

#### Success `200`

```json
{
  "success": true,
  "message": "Data fetched successfully.",
  "data": {
    "key": "store",
    "label": "Store",
    "enabled": true,
    "available": true,
    "subscribed": true
  }
}
```

| Field | Type | Meaning |
|---|---|---|
| `enabled` | bool | Store module is active for this tenant |
| `available` | bool | Same as `enabled` (alias for UI) |
| `subscribed` | bool | Same as `enabled` (alias for UI) |

When not subscribed / expired:

```json
{
  "success": true,
  "message": "Data fetched successfully.",
  "data": {
    "key": "store",
    "label": "Store",
    "enabled": false,
    "available": false,
    "subscribed": false
  }
}
```

---

## 2) All modules (optional)

### `GET /api/tenant/modules`

```json
{
  "success": true,
  "message": "Data fetched successfully.",
  "data": {
    "store": {
      "key": "store",
      "label": "Store",
      "enabled": true,
      "available": true,
      "subscribed": true
    },
    "modules": {
      "store": { "key": "store", "label": "Store", "enabled": true },
      "pos": { "key": "pos", "label": "POS", "enabled": false },
      "crm": { "key": "crm", "label": "CRM", "enabled": false },
      "accounting": { "key": "accounting", "label": "Accounting", "enabled": false }
    },
    "enabled_modules": ["store"]
  }
}
```

Use `data.store.enabled` or `data.enabled_modules.includes('store')`.

---

## 3) Store commerce APIs — already gated

These endpoints require an **active store package** (or full package).  
Middleware: `EnsureTenantModuleActive:store`.

| Method | Path |
|---|---|
| GET | `/api/tenant/products` |
| GET | `/api/tenant/products/{slug}` |
| GET | `/api/tenant/products/{slug}/similar` |
| GET | `/api/tenant/categories` |
| GET | `/api/tenant/categories/{slug}` |
| GET | `/api/tenant/governorates` |
| POST | `/api/tenant/contacts` |
| GET/POST | `/api/tenant/favorites` (auth) |
| POST/GET/DELETE… | `/api/tenant/cart/...` |
| POST | `/api/tenant/checkout/{token}` |
| GET | `/api/tenant/orders/{token}` |
| GET/POST | `/api/tenant/my-orders...` (auth) |
| GET/POST | `/api/tenant/reviews...` |
| GET | `/api/tenant/home` |
| GET | `/api/tenant/settings` |
| GET | `/api/tenant/footer` |
| GET | `/api/tenant/contact-us/page-data` |
| GET | `/api/tenant/branches` |
| GET | `/api/tenant/pages` |

### When store module is OFF — response `403`

```json
{
  "success": false,
  "message": "This module is not available for the current store subscription.",
  "error": {
    "code": "module_inactive",
    "required_modules": ["store"]
  }
}
```

Frontend handling:

1. On app boot → `GET /api/tenant/modules/store`
2. If `data.enabled === false` → block storefront UI
3. If any commerce call returns `403` + `error.code === "module_inactive"` → same fallback screen

---

## 4) Not gated by store module

| Path | Notes |
|---|---|
| `/api/tenant/auth/*` | Login / register / password |
| `/api/tenant/profile/*` | Customer profile (auth) |
| `/api/tenant/modules` | Module status |
| `/api/tenant/modules/store` | Store status |

---

## 5) Suggested frontend flow

```text
boot
  → GET /api/tenant/modules/store
  → if !enabled → show "Store unavailable"
  → else → GET /api/tenant/home + settings + products …
```

Dev note: with `BYPASS_PERMISSIONS=true` (default outside production) every module appears enabled. Test with `BYPASS_PERMISSIONS=false` and a tenant without an active store package.
