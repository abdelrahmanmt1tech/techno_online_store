# خطة نظام السلة والطلبات والشحن

## نظرة عامة

بناء نظام API كامل للسلة والطلبات والشحن للمنصة متعددة المستأجرين:
- **سلة تسوق** تعتمد على Token (UUID) — رابط قابل للمشاركة
- **نظام طلبات** مع حالات متعددة وتتبع بالرابط
- **checkout للضيوف** بدون تسجيل دخول — يكفي الرابط
- **API للمتجر** — واجهة برمجية للواجهة الأمامية (Headless)
- **محافظات** — جدول الشحن لكل مستأجر (إدخال يدوي)
- **كوبونات خصم** — أنواع متعددة (نسبة مئوية / مبلغ ثابت) مع حد أقصى للاستخدام

---

## المرحلة 1: قواعد البيانات (Migration)

### 1.1 جدول المحافظات `governorates`

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | اسم المحافظة |
| `shipping_cost` | decimal(10,2) | تكلفة الشحن، الافتراضي 0 |
| `is_active` | boolean | هل المحافظة نشطة، الافتراضي true |
| `timestamps` | — | created_at, updated_at |

### 1.2 جدول السلات `carts`

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint | PK |
| `token` | string | فريد (UUID) — يُستخدم في رابط المشاركة |
| `session_id` | string | nullable —ربط السلة بالمتصفح |
| `governorate_id` | FK | nullable — المحافظة المختارة |
| `coupon_id` | FK | nullable — الكوبون المطبق |
| `subtotal` | decimal(12,2) | المجموع الفرعي |
| `discount` | decimal(12,2) | مبلغ الخصم، الافتراضي 0 |
| `shipping_cost` | decimal(12,2) | تكلفة الشحن |
| `total` | decimal(12,2) | المجموع الكلي |
| `status` | enum | active, abandoned, converted |
| `timestamps` | — | — |

### 1.3 جدول عناصر السلة `cart_items`

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint | PK |
| `cart_id` | FK | cascade delete |
| `product_id` | FK | cascade delete |
| `product_variant_id` | FK | nullable, cascade delete |
| `quantity` | integer | الكمية، الافتراضي 1 |
| `unit_price` | decimal(12,2) | سعر الوحدة عند الإضافة |
| `total_price` | decimal(12,2) | السعر الإجمالي |
| `timestamps` | — | — |

### 1.4 جدول الطلبات `orders`

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint | PK |
| `order_number` | string | فريد — رقم تسلسلي #1001, #1002... |
| `token` | string | فريد — للتتبع عبر الرابط |
| `cart_id` | FK | nullable, null on delete |
| `status` | enum | pending, confirmed, processing, shipped, delivered, cancelled, returned |
| `customer_name` | string | اسم العميل |
| `customer_phone` | string | رقم الهاتف |
| `customer_email` | string | nullable |
| `customer_address` | text | العنوان التفصيلي |
| `governorate_id` | FK | nullable |
| `governorate_name` | string | نسخة احتياطية من اسم المحافظة |
| `shipping_cost` | decimal(12,2) | تكلفة الشحن عند الطلب |
| `coupon_id` | FK | nullable — الكوبون المستخدم |
| `coupon_code` | string | nullable — نسخة احتياطية من رمز الكوبون |
| `discount` | decimal(12,2) | مبلغ الخصم، الافتراضي 0 |
| `subtotal` | decimal(12,2) | المجموع الفرعي |
| `total` | decimal(12,2) | المجموع الكلي |
| `notes` | text | nullable — ملاحظات العميل |
| `timestamps` | — | — |

### 1.5 جدول عناصر الطلب `order_items`

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint | PK |
| `order_id` | FK | cascade delete |
| `product_id` | FK | cascade delete |
| `product_variant_id` | FK | nullable, cascade delete |
| `product_name` | string | نسخة احتياطية من اسم المنتج |
| `product_sku` | string | nullable — نسخة احتياطية |
| `variant_options` | json | nullable — مثال: `{"Color":"Red","Size":"XL"}` |
| `quantity` | integer | الكمية |
| `unit_price` | decimal(12,2) | سعر الوحدة |
| `timestamps` | — | — |

> **ملاحظة:** السعر الإجمالي لكل عنصر يُحسب تلقائياً من `unit_price × quantity` في الـ API، لا حاجة لتخزينه.

### 1.6 جدول الكوبونات `coupons`

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint | PK |
| `code` | string | فريد — رمز الكوبون (يُخزّن بحروف كبيرة) |
| `type` | enum | percentage (نسبة مئوية)، fixed (مبلغ ثابت) |
| `value` | decimal(12,2) | قيمة الخصم (نسبة أو مبلغ) |
| `minimum_order_amount` | decimal(12,2) | الحد الأدنى للطلب لتفعيل الكوبون، الافتراضي 0 |
| `maximum_discount_amount` | decimal(12,2) | nullable — الحد الأقصى للخصم (يُستخدم مع النسبة المئوية فقط) |
| `usage_limit` | integer | nullable — الحد الأقصى لعدد مرات الاستخدام الكلي |
| `usage_count` | integer | عدد مرات الاستخدام الحالي، الافتراضي 0 |
| `per_user_limit` | integer | nullable — الحد الأقصى للاستخدام لكل عميل |
| `is_active` | boolean | هل الكوبون نشط، الافتراضي true |
| `starts_at` | timestamp | nullable — تاريخ بدء الصلاحية |
| `expires_at` | timestamp | nullable — تاريخ انتهاء الصلاحية |
| `timestamps` | — | — |

### 1.7 جدول استخدامات الكوبونات `coupon_usages`

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint | PK |
| `coupon_id` | FK | cascade delete |
| `order_id` | FK | cascade delete |
| `customer_identifier` | string | nullable — session_id أو بريد العميل (للتحقق من الحد لكل عميل) |
| `discount_amount` | decimal(12,2) | مبلغ الخصم الفعلي المطبق |
| `timestamps` | — | — |

> **ملاحظة:** عند تطبيق الكوبون في checkout، يتم التحقق من: الصلاحية، الحد الأدنى للطلب، الحد الكلي للاستخدام، الحد لكل عميل (حسب `customer_identifier`)، ثم يُ incremented `usage_count` ويُنشأ سجل في `coupon_usages`.

---

## المرحلة 2: الموديلات (Tenant DB)

| الموديل | الملف | العلاقات |
|---|---|---|
| `Governorate` | `app/Models/Tenant/Governorate.php` | — |
| `Coupon` | `app/Models/Tenant/Coupon.php` | `usages()` HasMany CouponUsage |
| `CouponUsage` | `app/Models/Tenant/CouponUsage.php` | `coupon()` BelongsTo, `order()` BelongsTo |
| `Cart` | `app/Models/Tenant/Cart.php` | `items()` HasMany CartItem, `governorate()` BelongsTo, `coupon()` BelongsTo |
| `CartItem` | `app/Models/Tenant/CartItem.php` | `cart()` BelongsTo, `product()` BelongsTo, `variant()` BelongsTo |
| `Order` | `app/Models/Tenant/Order.php` | `items()` HasMany OrderItem, `governorate()` BelongsTo, `cart()` BelongsTo, `coupon()` BelongsTo, `couponUsages()` HasMany CouponUsage |
| `OrderItem` | `app/Models/Tenant/OrderItem.php` | `order()` BelongsTo, `product()` BelongsTo, `variant()` BelongsTo |

---

## المرحلة 3: Controllers (API)

### 3.1 ProductController — المنتجات (عام)

| الطريقة | الرابط | الوصف |
|---|---|---|
| `GET` | `/api/products` | قائمة المنتجات (فلتر بالتصنيف، ترقيم صفحات) |
| `GET` | `/api/products/{slug}` | تفاصيل المنتج مع الأنواع والوسائط |

### 3.2 GovernorateController — المحافظات (عام)

| الطريقة | الرابط | الوصف |
|---|---|---|
| `GET` | `/api/governorates` | قائمة المحافظات النشطة |

### 3.3 CartController — السلة

| الطريقة | الرابط | الوصف |
|---|---|---|
| `POST` | `/api/cart` | إنشاء سلة جديدة (يُرجع token) |
| `GET` | `/api/cart/{token}` | عرض السلة مع العناصر والمجموع |
| `POST` | `/api/cart/{token}/items` | إضافة منتج للسلة |
| `PUT` | `/api/cart/{token}/items/{item}` | تعديل الكمية |
| `DELETE` | `/api/cart/{token}/items/{item}` | حذف عنصر من السلة |
| `PUT` | `/api/cart/{token}/governorate` | تحديد المحافظة (يعيد حساب الشحن) |
| `POST` | `/api/cart/{token}/coupon` | تطبيق كوبون خصم (يُعيد السلة مع الخصم) |
| `DELETE` | `/api/cart/{token}/coupon` | إزالة الكوبون من السلة |

### 3.4 CheckoutController — إتمام الطلب

| الطريقة | الرابط | الوصف |
|---|---|---|
| `POST` | `/api/checkout/{token}` | تأكيد الطلب من السلة (يُرجع token الطلب) |
| `GET` | `/api/orders/{token}` | تفاصيل الطلب للتتبع |

### 3.5 CouponController — الكوبونات (للإدارة فقط — يُستخدم من Filament)

> **ملاحظة:** لا يوجد endpoint عام للكوبونات. التطبيق يتم من خلال `POST /api/cart/{token}/coupon` فقط.

---

## المرحلة 4: Routes

```php
// routes/tenant.php — داخل middleware tenancy
Route::prefix('api')->group(function () {
    // المنتجات (عام)
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);

    // المحافظات (عام)
    Route::get('governorates', [GovernorateController::class, 'index']);

    // السلة
    Route::post('cart', [CartController::class, 'store']);
    Route::get('cart/{token}', [CartController::class, 'show']);
    Route::post('cart/{token}/items', [CartController::class, 'addItem']);
    Route::put('cart/{token}/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('cart/{token}/items/{item}', [CartController::class, 'removeItem']);
    Route::put('cart/{token}/governorate', [CartController::class, 'setGovernorate']);

    // الكوبونات
    Route::post('cart/{token}/coupon', [CartController::class, 'applyCoupon']);
    Route::delete('cart/{token}/coupon', [CartController::class, 'removeCoupon']);

    // إتمام الطلب والتتبع
    Route::post('checkout/{token}', [CheckoutController::class, 'store']);
    Route::get('orders/{token}', [OrderController::class, 'show']);
});
```

---

## المرحلة 5: Filament Resources (لوحة التحكم للمستأجر)

### 5.1 GovernorateResource — إدارة المحافظات

**بنية المجلدات (مثل باقي الريسورسات):**
```
Governorates/
├── GovernorateResource.php          # orchestrator — يُعرّف model + nav + labels + يُحوّل form/table
├── Schemas/
│   └── GovernorateForm.php          # static configure(Schema): Schema
├── Tables/
│   └── GovernoratesTable.php        # static configure(Table): Table
└── Pages/
    ├── CreateGovernorate.php        # extends CreateRecord
    ├── EditGovernorate.php          # extends EditRecord
    └── ListGovernorates.php         # extends ListRecords + CreateAction
```

- **النموذج:** الاسم | تكلفة الشحن | حالة التنشيط (toggle)
- **الترجمة:** `__('dashboard.governorate')` / `__('dashboard.governorates')`

### 5.2 OrderResource — إدارة الطلبات

**بنية المجلدات:**
```
Orders/
├── OrderResource.php
├── Tables/
│   └── OrdersTable.php
└── Pages/
    ├── ViewOrder.php               # extends ViewRecord (تفاصيل الطلب — للقراءة فقط)
    └── ListOrders.php              # extends ListRecords (بدون CreateAction)
```

- **الجدول:** رقم الطلب | اسم العميل | الحالة (badge ملون) | المجموع | المحافظة | التاريخ
- **صفحة التفاصيل:** عناصر الطلب | معلومات العميل | تحديث الحالة
- **الإجراءات:** تغيير الحالة (pending → confirmed → processing → shipped → delivered / cancelled / returned)
- **ملاحظة:** لا يوجد Create/Edit — الطلب يُنشأ من الـ API فقط

### 5.3 CouponResource — إدارة الكوبونات

**بنية المجلدات:**
```
Coupons/
├── CouponResource.php
├── Schemas/
│   └── CouponForm.php
├── Tables/
│   └── CouponsTable.php
└── Pages/
    ├── CreateCoupon.php
    ├── EditCoupon.php
    └── ListCoupons.php
```

- **الجدول:** الرمز | النوع (badge) | القيمة | الحد الأدنى | مرات الاستخدام | الحالة (toggle) | الصلاحية
- **النموذج:** رمز الكوبون | النوع (نسبة/مبلغ) | القيمة | الحد الأدنى للطلب | الحد الأقصى للخصم | حد الاستخدام الكلي | حد الاستخدام لكل عميل | تبديل التنشيط | تاريخ البدء | تاريخ الانتهاء

---

## المرحلة 6: الصلاحيات (Tenant Permissions)

```php
// app/Helper/TenantPermissionsArray.php — الإضافات
'governorates' => ['view', 'create', 'update', 'delete'],
'orders' => ['view', 'update'],
'coupons' => ['view', 'create', 'update', 'delete'],
```

---

## القرارات التصميمية المهمة

| القرار | الاختيار | السبب |
|---|---|---|
| هوية السلة | Token (UUID) في الرابط | رابط قابل للمشاركة: `store.tenant.com/checkout/{token}` |
| هوية الطلب | token + order_number منفصلين | token للوصول عبر API، order_number للعرض |
| إدارة المخزون | خصم الكمية عند تأكيد الطلب فقط إذا كان `track_stock = true` | المنتجات التي لا تتبع المخزون لا تتأثر |
| نسخ المتغيرات | JSON في order_items | يحافظ على سجل الطلب حتى لو تم تعديل النوع |
| نسخ الأسعار | unit_price في cart_items + order_items | السعر يُقفل عند وقت الإضافة للسلة |
| المحافظات | إدخال يدوي من المستأجر | حسب التفضيل |
| الكوبونات | رمز نصي + نوع (نسبة/مبلغ) مع حد أقصى للاستخدام | مرن وقابل للتوسع |

---

## منطق الكوبونات

### قواعد التحقق عند تطبيق الكوبون (`POST /api/cart/{token}/coupon`)

1. **الكوبون موجود ونشط** — `is_active = true`
2. **الصلاحية** — `starts_at` في الماضي و `expires_at` في المستقبل (أو null)
3. **الحد الكلي** — `usage_count < usage_limit` (إذا كان `usage_limit` غير null)
4. **الحد لكل عميل** — عدد استخدامات `customer_identifier` الحالي < `per_user_limit` (إذا كان `per_user_limit` غير null)
5. **الحد الأدنى** — مجموع السلة (subtotal) >= `minimum_order_amount`
6. **حساب الخصم:**
   - **النسبة المئوية:** `discount = subtotal × (value / 100)` — إذا يوجد `maximum_discount_amount` يُقص عليه
   - **المبلغ الثابت:** `discount = value` — لكن لا يتجاوز subtotal

### عند تأكيد الطلب (`POST /api/checkout/{token}`)

1. إعادة التحقق من الكوبون (الصلاحية + الاستخدام)
2. زيادة `usage_count` في جدول `coupons`
3. إنشاء سجل في `coupon_usages` مع `discount_amount` الفعلي
4. حفظ `coupon_id` و `coupon_code` و `discount` في الطلب

### إزالة الكوبون (`DELETE /api/cart/{token}/token`)

- يُزيّد `coupon_id` و `discount` من السلة
- يُعيد حساب `total` بدون الخصم

---

## ترتيب التنفيذ المقترح

1. **المراحل 1+2:** Migration + Models (9 ملفات)
2. **المرحلة 3:** Controllers (5 ملفات)
3. **المرحلة 4:** Routes (تعديل routes/tenant.php)
4. **المرحلة 5:** Filament Resources (3 resources)
5. **المرحلة 6:** Permissions + الترجمات (en/ar)
6. **الاختبار:** PHPUnit tests للـ API endpoints

---

## الملفات الجديدة المطلوبة

```
database/migrations/tenant/
├── 2026_07_15_000002_create_governorates_table.php
├── 2026_07_15_000003_create_carts_table.php
├── 2026_07_15_000004_create_cart_items_table.php
├── 2026_07_15_000005_create_orders_table.php
├── 2026_07_15_000006_create_order_items_table.php
├── 2026_07_15_000007_create_coupons_table.php
└── 2026_07_15_000008_create_coupon_usages_table.php

app/Models/Tenant/
├── Governorate.php
├── Coupon.php
├── CouponUsage.php
├── Cart.php
├── CartItem.php
├── Order.php
└── OrderItem.php

app/Http/Controllers/Api/Tenant/
├── ProductController.php
├── GovernorateController.php
├── CartController.php
├── CheckoutController.php
└── OrderController.php

app/Filament/Tenant/Resources/
├── Governorates/
│   ├── GovernorateResource.php
│   ├── Schemas/
│   │   └── GovernorateForm.php
│   ├── Tables/
│   │   └── GovernoratesTable.php
│   └── Pages/
│       ├── CreateGovernorate.php
│       ├── EditGovernorate.php
│       └── ListGovernorates.php
├── Orders/
│   ├── OrderResource.php
│   ├── Tables/
│   │   └── OrdersTable.php
│   └── Pages/
│       ├── ViewOrder.php
│       └── ListOrders.php
└── Coupons/
    ├── CouponResource.php
    ├── Schemas/
    │   └── CouponForm.php
    ├── Tables/
    │   └── CouponsTable.php
    └── Pages/
        ├── CreateCoupon.php
        ├── EditCoupon.php
        └── ListCoupons.php

lang/en/dashboard.php (تعديل — إضافة مفاتيح جديدة)
lang/ar/dashboard.php (تعديل — إضافة مفاتيح جديدة)
```
