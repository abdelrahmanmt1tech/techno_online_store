# خطة تعديل نظام الاشتراكات (Packages)

**الحالة:** خطة معتمدة للتنفيذ  
**التاريخ:** 2026-08-03

> **حالة التنفيذ (2026-08-03):** أُنجز النطاق Admin + API + Gating. انظر «9. ملاحظات التنفيذ» أسفلها لانحرافين مقصودين عن النص الحرفي.

## 1. القرارات المعتمدة

| القرار | الخيار |
|---|---|
| `module_id` | عمود نصي `module` بقيم `TenantModule` (store/pos/crm/accounting) — لا يوجد جدول `modules`. الـ full package يكون `module = null`. |
| أسعار الدفع | `price` + `duration` + `duration_type` بقيم `day / month / year` (بدل monthly/yearly). |
| المميزات | إسقاط `plan_features` من التدفق الجديد نهائيًا. |
| النطاق | Admin + API + Gating فقط (بدون بوابات دفع). |
| الـ Gating | صارم: أي عميل بلا package نشط يفقد الوحدات. |

## 2. الجداول الجديدة (central DB)

### 2.1 `packages`

| العمود | النوع | ملاحظات |
|---|---|---|
| id | bigint PK | |
| module | string nullable, index | قيمة `TenantModule`، null عندما `is_full_package = true` |
| is_full_package | boolean default false | يمنح كل الوحدات |
| name | json | مترجم ar/en |
| desc | json nullable | مترجم ar/en |
| trials_duration | unsignedInteger default 0 | بالأيام |
| sort | unsignedInteger default 0 | |
| is_active | boolean default true | |
| timestamps | | |

قاعدة: `is_full_package = true` ⇒ `module = null`.

### 2.2 `prices`

| العمود | النوع | ملاحظات |
|---|---|---|
| id | bigint PK | |
| package_id | FK `packages` | cascadeOnDelete |
| country_id | FK `countries` | cascadeOnDelete |
| currency_id | FK `currencies` | cascadeOnDelete |
| price | decimal(12,2) | |
| duration | unsignedInteger | مثلًا 6 |
| duration_type | enum(day, month, year) | مثلًا month |
| timestamps | | |

Constraint: `unique(package_id, country_id, currency_id, duration, duration_type)`.

### 2.3 `tenant_packages`

| العمود | النوع | ملاحظات |
|---|---|---|
| id | bigint PK | |
| tenant_id | string(36) FK `tenants` | cascadeOnDelete |
| package_id | FK `packages` | cascadeOnDelete |
| price | decimal(12,2) | snapshot من `prices` وقت الاشتراك |
| currency_id | FK `currencies` | snapshot |
| duration | unsignedInteger | snapshot |
| duration_type | enum(day, month, year) | snapshot |
| started_at | dateTime | |
| trial_ends_at | dateTime nullable | `started_at + trials_duration` إن وُجد |
| expires_at | dateTime nullable | `(trial_ends_at ?? started_at) + duration` |
| status | enum(trial, active, expired, cancelled) default active | |
| timestamps | | |

Index: `(tenant_id, status)`.

## 3. ملفات جديدة

- Migrations (3): `packages`, `prices`, `tenant_packages`.
- `app/Models/Package.php` — translatable (`name`, `desc`)، علاقة `prices()` HasMany، ودالة `enabledModules(): array` (full → كل `TenantModule::cases()`، وإلا `[module]`).
- `app/Models/PackagePrice.php` — belongsTo `Package` / `Country` / `Currency`.
- `app/Models/TenantPackage.php` — belongsTo `Tenant` / `Package`، سكوب `active()` (status في trial/active ولم ينتهِ).
- `app/Filament/Resources/Packages/` — `PackageResource` + `Schemas/PackageForm.php` + `Tables/PackagesTable.php` + `Pages/{CreatePackage,EditPackage,ListPackages}.php`.
  - `PackageForm`: name/desc ar/en، Toggle `is_full_package` يُخفي/يعطّل Select `module`، `trials_duration`، `sort`، `is_active`، Repeater `prices` (relationship) بحقول: country Select + currency Select + price + duration + duration_type، مع زر إضافة بلد/عملة من داخل النموذج (مثل الموجود في `PlanForm`).
- `app/Http/Resources/Central/PackageResource.php`.

## 4. التعديلات على الملفات الموجودة

| الملف | التعديل |
|---|---|
| `app/Models/Tenant.php` | إضافة علاقة `packages()` HasMany (tenant_packages). |
| `app/Filament/Resources/Tenants/Schemas/TenantForm.php` | استبدال `plan_id` بـ `package_id` + Select للسعر من `prices` بحسب `country_id/currency_id` المختارين في نفس النموذج (best-match ثم أول سعر للبلد ثم أول سعر عام)، auto-fill للـ duration/duration_type/trial/expiry. |
| `app/Filament/Resources/Tenants/Pages/CreateTenant.php` | كتابة صف `tenant_packages` بدل `tenant_subscriptions`. |
| `app/Filament/Resources/Tenants/Pages/EditTenant.php` | إدارة `tenant_packages` (إنشاء/تحديث/إلغاء) بدل `tenant_subscriptions`. |
| `app/Http/Requests/Central/StoreTenantRequest.php` | استبدال `plan_id` بـ `package_id` (+ `price`/`duration`/`duration_type` اختيارية). |
| `app/Http/Controllers/Api/Central/TenantController.php` | كتابة `tenant_packages` بدل `tenant_subscriptions`. |
| `app/Support/Modules/TenantModuleGate.php` | تنفيذ `resolve()` الفعلي (القسم 5). |
| `app/Http/Controllers/Api/Central/HomeController.php` | `getHomeData()` يُرجع `packages` بدل `plans` عبر `PackageResource`، مع `country_id` اختياري لتصفية الأسعار. |
| `database/seeders/HomePageDataSeeder.php` | الـ guard من `Plan::exists()` إلى `Package::exists()`، واستبدال بلوك plans بـ packages + prices (full package + package لكل وحدة بأسعار SAR/EGP). |
| `docs/tenant-modules.md` | تحديث الحالة: wiring مبدئي لـ billing عبر packages. |

## 5. منطق الـ Gating (`TenantModuleGate::resolve()`)

```php
private static function resolve(TenantModule $module): bool
{
    $enabled = once(fn () => self::enabledModulesForCurrentTenant());

    return in_array($module, $enabled, true);
}

private static function enabledModulesForCurrentTenant(): array
{
    // tenant_packages النشطة للعميل الحالي
    // (status trial/active و expires_at > now)
    // full package → كل الوحدات
    // وإلا → modules المستخرجة من كل package
}
```

- الـ trial يُعد نشطًا (لأن `expires_at` يُحسب بعد انتهاء التجربة).
- بدون أي package نشط → لا وحدات (gating صارم).
- يُنفذ `once()` لكل request لتجنب تكرار الاستعلام.

## 6. القديم (legacy — حُذف)

- تم حذف جداول `plans` / `plan_features` / `tenant_subscriptions` + نموذج `Plan` / `PlanFeature` / `TenantSubscription` + مورد `Plans` + Widget `TenantSubscriptionStatusPie` عند استكمال الهجرة إلى `packages` / `tenant_packages`.
- الـ gating يستخدم `tenant_packages` فقط عبر `TenantModuleGate`.

## 7. ملاحظات حرجة قبل التنفيذ

1. **الـ Gating صارم**: في production أي عميل بلا package نشط يفقد الوحدات. يجب قبل التفعيل تعيين packages لكل العملاء الحاليين — سكربت تحويل (أو Seeder) يمنح كل عميل package مقابل الاشتراك الحالي.
2. **تغيير مكسر**: `/api/home` يغيّر `plans` → `packages` — يجب تحديث الواجهة الخارجية وملفات Postman collections في `docs/`.

## 8. الاختبارات

- تحديث `tests/Feature/Api/Front/TenantControllerTest.php` (plan_id → package_id).
- اختبار جديد لـ `TenantModuleGate`: full package، package جزئي، package منتهي، trial، بدون packages.
- اختبار Home API: إرجاع `packages` مع الأسعار.

## 9. ملاحظات التنفيذ (2026-08-03)

انحرافان مقصودان عن النص الحرفي للخطة، لمراعاة بيئة التطوير وقواعد المستودع:

1. **Dev bypass**: `TenantModuleGate::resolve()` يستثني التطوير عبر `config('app.bypass_permissions')` (افتراضيًا `true` خارج production، وفق AGENTS.md). بدون هذا سيفقد كل عميل في التطوير وحداته قبل تهيئة packages له.
2. **Guard السـeeder**: `HomePageDataSeeder` يخرج مبكرًا عندما `Plan::exists() || Package::exists()` (بدل `Package::exists()` فقط) — حتى لا يكرر قواعد البيانات (تصنيفات/ثيمات/مدونات/أسئلة) على قواعد قديمة تحوي plans بلا packages.

اختبارات النطاق:
- `tests/Feature/Modules/TenantModuleGateTest.php` — full/جزئي/متعدد/منتهي/trial/cancelled/بدون/بypass.
- `tests/Feature/Api/Front/TenantControllerTest.php` — `package_id` + صف `tenant_packages`.
- `tests/Feature/Api/Front/HomeControllerTest.php` — `data.plans.items` يعرض packages مع الأسعار.
