# خطة تفعيل gating وحدة CRM على الموارد والصفحات

**الهدف:** التينانت المشترك في باقة `crm` يرى صفحات/موارد CRM فقط طوال مدة الاشتراك، وتختفي فور انتهائها.

---

## الوضع الحالي (الجذر)

- `HasTenantFeatureAccess::passesTenantFeatureGate()` يرجع `true` ثابتًا → كل رويسورسات CRM (8) وصفحات CRM (9) وWidgets (7) التي تستدعيه لا تُقيَّد فعليًا.
- `ClientResource` و`LeadSourceResource` (رويسورسات CRM تعيش في مسار Tenant) لا تمتد من `CrmResource` → غير مقيّدة إطلاقًا.
- لوحة `/app/crm` قابلة للدخول دائمًا (حتى لو فُقد المحتوى).

---

## التعديلات

### 1) تفعيل البوابة (التغيير الجوهري)

**الملف:** `app/Filament/Concerns/HasTenantFeatureAccess.php`

- استبدال stub `return true;` بـ:
  ```php
  return tenant_module_enabled(TenantModule::Crm);
  ```
- **تم التنفيذ (2026-08-05):** البوابة فعّالة؛ Client/LeadSource يمتدان `CrmResource`؛ لوحة `/app/crm` تستخدم `EnsureTenantModuleActive:crm`.
- سطر واحد يقفل تلقائيًا:
  - الـ 8 رويسورسات عبر `CrmResource::canViewAny()` / `shouldRegisterNavigation()`.
  - الـ 9 صفحات عبر `CrmPage::canAccess()`.
  - الـ 7 Widgets عبر `canView()`.
- النتيجة: تختفي العناصر من sidebar لوحة Tenant ولوحة CRM، والوصول المباشر للرابط يرجع 403.

### 2) ربط رويسورسات العملاء ومصادر العملاء

**الملفات:**
- `app/Filament/Tenant/Resources/Clients/ClientResource.php`
- `app/Filament/Tenant/Resources/LeadSources/LeadSourceResource.php`

- تغيير `extends Resource` إلى `extends CrmResource`.
- نقل تحققات الصلاحيات الحالية إلى دوال `*ByPermission()`:
  - `ClientResource`: `clients.view` / `clients.create` / `clients.update` / `clients.delete` / `clients.restore` / `clients.force_delete` / `clients.delete_bulk` / `clients.restore_bulk` / `clients.force_delete_bulk`.
  - `LeadSourceResource`: نفس النمط بمفاتيح `lead_sources.*`.
- إزالة الـ overrides المكررة (`shouldRegisterNavigation`, `canViewAny`, `canCreate`, ...) لأنها موروثة من `CrmResource` مع البوابة.
- الإبقاء على `canAccess()` مُقيَّدًا بالبوابة للتوافق.

### 3) حجب لوحة `/app/crm` بالكامل

**ملف جديد:** `app/Http/Middleware/EnsureCrmModuleActive.php`

- يرجع `abort(404)` عندما `! tenant_module_enabled(TenantModule::Crm)`.
- تسجيله في `app/Providers/Filament/CrmPanelProvider.php` في `->middleware([...])` بعد `EnsureTenantIsInitialized` (يحتاج سياق التينانت).
- الـ 404 لإخفاء وجود اللوحة.

### 4) تحديث التوثيق (خفيف)

- `docs/crm-accounting-port.md` سطر 19: تعديل «gate stub always true» إلى أن بوابات CRM مفعّلة.
- `docs/tenant-modules.md`: ملاحظة أن رويسورسات/صفحات CRM مقيّدة الآن عبر `tenant_module_enabled('crm')`.

---

## سلوك التطوير والاختبار

- الـ bypass محترم كما هو: في التطوير تبقى كل الوحدات مفتوحة، والبوابة فعّالة في الإنتاج أو عند `BYPASS_PERMISSIONS=false`.
- للاختبار: عيّن `BYPASS_PERMISSIONS=false` ثم ادخل على تينانت **بدون** باقة crm نشطة → تختفي عناصر CRM من الـ sidebar و`/app/crm` ترجع 404. تينانت عليه باقة crm أو باقة كاملة → تظهر.
- لا تُضاف مفاتيح صلاحيات جديدة (لا مخالفة لـ AGENTS.md).

---

## نقاط تحقق بعد التنفيذ

- `./vendor/bin/pint` على الملفات المعدّلة.
- `composer run test` (خصوصًا اختبارات CRM/ERP التي لا يجب أن تتأثر).
