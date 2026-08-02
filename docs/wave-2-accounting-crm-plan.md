# موجة 2 — محاسبة متكاملة + تعزيز CRM (خطة شاملة)

**الحالة:** قيد التنفيذ (2026-08-02) — أجزاء 2.0–2.5 بدأت  
**الأساس:** انتهت الموجة 1 (شجرة، دليل، ميزان، أستاذ، كشف ذمم، إعدادات، Entry-only) على `dev`  
**المراجع:** [`docs/accounting-crm-qa.md`](accounting-crm-qa.md)، [`docs/crm-accounting-port.md`](crm-accounting-port.md)، [`docs/erp-core-architecture.md`](erp-core-architecture.md)

---

## الهدف

جعل المحاسبة **متصلة فعلياً** بحركة المتجر (فواتير/مدفوعات/مخزون تكلفة)، وفي نفس الوقت رفع قيمة الـ CRM من «منقول» إلى «قابل للاستخدام يومياً + مرتبط بالتجارة».

---

## قرارات مثبتة مسبقاً (لا تُعاد مناقشتها إلا بطلب صريح)

| قرار | الاختيار |
|---|---|
| لا Payment / SafesBank / AccountStatement الطيران | يبقى |
| لا Meta messaging داخل لوحة CRM | يبقى (messaging في Tenant منفصل) |
| لا Observers لترحيل المخزون أو القيود | ترحيل فقط من Actions/Services صريحة |
| صلاحيات Spatie | مؤجّلة لنهاية الموجة أو pre-prod |
| Excel الكامل | اختياري في ذيل الموجة، ليس مانعاً للإغلاق |
| **نموذج البيع** | **موديولات باشتراك منفصل لكل موديول** — إلغاء الاعتماد على خطة/باقة entitlements |
| بوابة الإتاحة | `TenantModuleGate` / `tenant_module_enabled()` — ترجع `true` حتى تفعيل الفوترة ([`docs/tenant-modules.md`](tenant-modules.md)) |
| **ترحيل تلقائي للقيود** | **فقط إذا المحاسبة نشطة:** `tenant_accounting_active()` |

---

## الجزء أ — ترحيل الفواتير والمدفوعات إلى القيود (العمود الفقري)

### أ.1 الوضع الحالي

| المستند | يكتب GL اليوم؟ |
|---|---|
| Operation يدوي + إغلاق فترة | نعم |
| SalesInvoice / PurchaseInvoice / InvoicePayment | **لا** |
| Sale confirm / POS checkout | مخزون/درج فقط — **لا GL** |
| SalesReturn / PurchaseReturn | **لا GL** |

`AccountingOperationWriter` جاهز؛ assert التوازن معطّل جزئياً (`todo26`) — يجب تفعيله قبل الشحن.

### أ.2 مفاتيح إعدادات جديدة (`TenantSetting` + صفحة AccountingSettings)

| المفتاح | حساب COA مرشّح | الاستخدام |
|---|---|---|
| `sales_revenue_account_tree_id` | `4101` | دائن فاتورة بيع |
| `sales_returns_account_tree_id` | `4191` | مردودات |
| `inventory_account_tree_id` | `1501` | مخزون |
| `cogs_account_tree_id` | `5110` | تكلفة مبيعات |
| `sales_tax_payable_account_tree_id` | `2103` | ضريبة مبيعات |
| `purchase_tax_receivable_account_tree_id` | `130` | ضريبة مشتريات (إن وُجدت) |
| `default_cash_account_tree_id` | `1101` | نقدية |
| `default_bank_account_tree_id` | `1102` | بنك/بطاقة |
| `default_wallet_account_tree_id` | `1103` | محفظة |
| `walk_in_ar_account_tree_id` | `1202` | ذمم نقدية/بدون عميل |
| `goods_received_clearing_account_tree_id` *(اختياري)* | حساب وسيط | إن اخترنا مسار GR→Invoice |

### أ.3 مصفوفة الترحيل المقترحة

**فاتورة بيع (عند الإصدار)**  
- مدين: ذمة العميل (`CLIENT#id` أو walk-in) = `grand_total`  
- دائن: إيرادات = صافي  
- دائن: ضريبة (إن > 0)  

**تكلفة البضاعة (مرافق أو منفصل، نفس الـ TX إن أمكن)**  
- مدين: COGS ← دائن: مخزون = `cost_total` / FIFO المخصوم  

**تحصيل فاتورة / POS payment**  
- مدين: نقدية/بنك/محفظة حسب طريقة الدفع  
- دائن: ذمة العميل  

**فاتورة شراء** *(بعد حسم قرار GR)*  
- مسار مفضّل: GR يرحّل مخزون مقابل clearing؛ الفاتورة تقلّب clearing → ذمة مورد  
- مسار مبسّط (موجة 2a): فاتورة شراء مدين مخزون / دائن مورد (إن لم يُرسمل GR محاسبياً بعد)

**مردود بيع**  
- عكس إيراد (+ضريبة) + عكس COGS/مخزون؛ `service` = SalesReturn  

**Sale confirm وحده**  
- **لا قيد إيراد** (لتجنب الازدواج مع الفاتورة)

### أ.4 تصميم تقني

```
app/Services/Accounting/Posting/
  ResolvePostingAccountsService.php
  PostSalesInvoiceToJournalService.php
  PostPurchaseInvoiceToJournalService.php
  PostInvoicePaymentToJournalService.php
  PostSalesReturnToJournalService.php
  FindSystemOperationService.php
```

- الاستدعاء من نهاية Actions الحالية داخل نفس الـ DB transaction  
- **قبل أي كتابة GL:** `if (! tenant_accounting_active()) return;`  
- Idempotency: `operations.service_type` + `service_id` (+ مميّز REV/COGS إن لزم)  
- `is_system_generated=true`، `is_posted/locked`، `linkable` = Client/Supplier  
- **ممنوع:** Observer / `boot()` للترحيل  

نقاط الربط:

| Action | خدمة |
|---|---|
| `CreateSalesInvoiceAction` | PostSalesInvoice (+ COGS) |
| `RecordInvoicePaymentAction` | PostInvoicePayment (يشمل POS عبر المحرك) |
| `CreatePurchaseInvoiceAction` | PostPurchaseInvoice |
| `PostSalesReturnAction` | PostSalesReturn |

### أ.5 ترتيب تنفيذ المحاسبة (داخلي الموجة)

1. إعدادات + seed defaults للمستأجرين الفارغين + توثيق للمستأجرين الموجودين  
2. تفعيل `assertEntriesBalanced` + ResolvePostingAccounts (فشل واضح إن نقص إعداد)  
3. ترحيل فاتورة البيع + اختبار Feature (توازن + idempotent)  
4. ترحيل المدفوعات (+ مسار POS)  
5. ترحيل فاتورة الشراء (بعد قرار GR)  
6. ترحيل دفع المورد  
7. COGS المرافق  
8. مردود البيع  
9. Smoke على tenant تجريبي + تحديث docs  
10. *(لاحق داخل الموجة أو ذيل)* مردود شراء، تسويات مخزون→GL، ربط درج النقدية بحساب، Excel

### أ.6 قرارات مفتوحة يجب حسمها قبل/أثناء التنفيذ

1. **رسملة المخزون عند GR أم عند فاتورة الشراء؟** (الأهم)  
2. توقيت COGS: مع الفاتورة أم مع confirm (التكلفة معروفة عند confirm؛ الفواتير الجزئية تحتاج نسبة)  
3. عميل walk-in بدون `account_tree_id`  
4. ازدواجية درج POS (`CashMovement`) مع حساب النقدية في GL — سياسة مطابقة  
5. ضريبة المشتريات غالباً 0 اليوم — حسابات قد تبقى غير مستخدمة مؤقتاً  
6. Backfill للفواتير التاريخية؟ (افتراضي: لا — من الآن فصاعداً فقط، مع أمر اختياري لاحقاً)

---

## الجزء ب — تعزيز CRM لزيادة القيمة (Store SaaS)

### ب.1 ما هو منقول أصلاً (لا نعيد بناؤه)

Pipeline: فرص، مراحل، متابعات، حملات، عمولات، دورات دفع، MyCommissions، 6 صفحات تقارير + تصدير، عملاء/مصادر، ربط `accTree` للعميل.

### ب.2 ناقص من flyaram ويستحق النقل (قيمة عالية)

| # | العنصر | لماذا |
|---|---|---|
| 1 | **LeadClients** صفحة صندوق العملاء المحتملين (`stage=lead`) | inbox يومي للمبيعات |
| 2 | **ربط Seeders** (`LeadSource`, `OpportunityStage`, `FollowUpType/Status`) في `TenantDataSeeder` | مستأجر جديد بلا lookups فارغة |
| 3 | **إصلاح Charts** (إعادة كتابة لـ Filament `ChartWidget::getData` بدل Apex stub المعطوب) | الداشبورد حالياً ضعيف/معطّل عملياً |
| 4 | **مسارات طباعة التقارير** (Controllers + routes؛ الـ Blades موجودة) | أزرار الطباعة تكسر حالياً |
| 5 | **تنظيف `User` → `TenantUser`** في Filament/Services CRM | يمنع فشل صلاحيات/فلاتر/رسوم |

### ب.3 إضافات تزيد قوة CRM (ليست نسخ flyaram — قيمة متجر)

مرتبة حسب قيمة العمل:

| الأولوية | الإضافة | الوصف |
|---|---|---|
| **P0** | استقرار المنفذ أعلاه (ب.2) | بدونها الـ CRM «موجود لكن مزعج» |
| **P1** | **جسر CRM ↔ تجارة** | ربط الفرصة بـ Sale / SalesInvoice / Order؛ مبلغ الإغلاق من فاتورة حقيقية؛ اختياري: إنشاء مسودة بيع من فرصة Won |
| **P1** | **LeadClients + تحويل Lead→Customer** | مسار واضح من محتمل إلى عميل متجر |
| **P2** | **مصادر تجزئة** | Walk-in، POS، Marketplace، هاتف + نموذج/API التقاط lead بدون Meta |
| **P2** | **`commission_percentage` في واجهة TenantUser** | العمود موجود؛ الواجهة غالباً ناقصة |
| **P2** | **تذكير المتابعات المستحقة** | قائمة/badge للبائع والمدير |
| **P3** | **ويدجت أداء موظف** على صفحة الموظف/المستخدم | رؤية مدير المبيعات |
| **P3** | **اختياري لاحقاً:** جسر Messaging Tenant → Lead (بدون إعادة منفذ Meta CRM كامل) | واتساب موجود في Tenant؛ ربطه بـ CRM lead عند الطلب فقط |
| **P3** | ترحيل صرف عمولة → قيد محاسبي | بعد استقرار GL في الجزء أ |
| **ذيل** | اختبارات CRM/عمولات (نقل مجموعة فرعية من ~35 في flyaram) + مفاتيح `crm_*` قبل الإنتاج | hardening |

### ب.4 ما لن نضيفه في الموجة 2 (صراحة)

- صفحات Meta CRM (inbox/connect/logs) من flyaram  
- InboundLeadCapturePool / Conversation→Opportunity كما في الطيران  
- IATA / تذاكر / Franchise  
- نسخ Payment الطيران لتسجيل عمولات بنكية

---

## الجزء ج — هيكل الموجة 2 كمراحل داخلية

حتى تبقى «الأكبر والأشمل» قابلة للتسليم:

| مرحلة | المحتوى | معيار الإغلاق |
|---|---|---|
| **2.0 Stabilize CRM** | Seeders + TenantUser cleanup + charts + print + LeadClients | CRM يُستخدم يومياً بدون أعطال واضحة |
| **2.1 GL Settings + Writer harden** | مفاتيح الترحيل + assert توازن | فشل واضح إن نقص إعداد |
| **2.2 Sales GL** | فاتورة بيع + تحصيل (+ POS) + اختبارات | قيد متوازن + idempotent |
| **2.3 Purchase GL** | فاتورة شراء + دفع مورد (بعد قرار GR) | نفس المعيار |
| **2.4 COGS + Returns** | تكلفة + مردود بيع | عكس صحيح |
| **2.5 CRM Commerce bridge** | ربط فرصة↔فاتورة/بيع + مصادر تجزئة + % عمولة UI + تذكير متابعات | قيمة تجارية ملموسة |
| **2.6 Hardening (ذيل)** | اختبارات CRM فرعية، docs، smoke store1؛ صلاحيات إن طُلب | جاهزية أقرب للإنتاج |

يمكن تنفيذ 2.0 بالتوازي مع 2.1؛ **2.2 هو قلب «المحاسبة المتكاملة»**.

---

## الجزء د — نطاق CRM الناقص (ملخص سريع للإجابة)

**ينقصكم لرفع قوة CRM:**

1. استقرار (charts، طباعة، seed، LeadClients، TenantUser)  
2. جسر مع المبيعات الحقيقية (الفرصة ليست معزولة عن الفاتورة)  
3. التقاط leads تجزئة (Walk-in/POS/موقع) بدون Meta  
4. مساءلة البائع (متابعات مستحقة + أداء)  
5. لاحقاً: اختبارات + صلاحيات + (اختياري) واتساب→lead  

**لا ينقصكم** لإطلاق pipeline أساسي: الفرص/العمولات/التقارير الجدولية منقولة.

---

## تقدير تقريبي (للتوجيه فقط)

| مرحلة | حجم تقريبي |
|---|---|
| 2.0 CRM stabilize | متوسط |
| 2.1–2.4 Accounting posting | **كبير** (الأضخم) |
| 2.5 CRM commerce value | متوسط–كبير |
| 2.6 Hardening | متوسط |

---

## معيار «انتهينا من الموجة 2»

- كل فاتورة بيع/شراء جديدة ومدفوعة تُنشئ قيوداً متوازنة وقابلة لإعادة التشغيل بدون تكرار  
- POS التحصيل يظهر في GL (نقدية) مع بقاء `CashMovement` كما هو  
- CRM: LeadClients + charts تعمل + seed تلقائي + طباعة تقارير + ربط فرصة بمستند بيع واحد على الأقل  
- توثيق محدّث في `docs/accounting-crm-qa.md` و`docs/crm-accounting-port.md` بما نجح/تبقّى  
- **ما زال مقبولاً أن يبقى خارج الإغلاق:** Excel الكامل، Meta، صلاحيات Spatie النهائية، backfill تاريخي

---

## الخطوة التالية بعد الموافقة

1. حسم قرار **GR vs فاتورة شراء للمخزون**  
2. تأكيد إدراج **2.0 + 2.5 CRM** داخل نفس الموجة (موصى به) أم فصل CRM stabilize كموجة 2a سريعة  
3. تحويل هذه الوثيقة إلى تنفيذ مرحلي مع todos
