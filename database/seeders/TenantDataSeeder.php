<?php

namespace Database\Seeders;

use App\Models\Tenant\Attribute;
use App\Models\Tenant\Brand;
use App\Models\Tenant\Category;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerContact;
use App\Models\Tenant\Governorate;
use App\Models\Tenant\Page;
use App\Models\Tenant\Product;
use App\Models\Tenant\Review;
use App\Models\TenantUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGovernorates();
        $categories = $this->seedCategories();
        $brands = $this->seedBrands();
        $attributes = $this->seedAttributes();
        $this->seedProducts($categories, $brands);
        $this->seedPages();
        $this->seedCoupons();
        $users = $this->seedDemoUsers();
        $this->seedCustomers($users);
        $this->seedReviews();
        $this->seedContacts();
        (new HomeSectionSeeder)->run();
    }

    protected function seedGovernorates(): void
    {
        $governorates = [
            ['name' => 'القاهرة', 'shipping_cost' => 50, 'is_active' => true],
            ['name' => 'الجيزة', 'shipping_cost' => 60, 'is_active' => true],
            ['name' => 'الإسكندرية', 'shipping_cost' => 70, 'is_active' => true],
            ['name' => 'القليوبية', 'shipping_cost' => 60, 'is_active' => true],
            ['name' => 'الفيوم', 'shipping_cost' => 80, 'is_active' => true],
            ['name' => 'البحيرة', 'shipping_cost' => 80, 'is_active' => true],
            ['name' => 'المنوفية', 'shipping_cost' => 70, 'is_active' => true],
            ['name' => 'الغربية', 'shipping_cost' => 70, 'is_active' => true],
            ['name' => 'كفر الشيخ', 'shipping_cost' => 80, 'is_active' => true],
            ['name' => 'دمياط', 'shipping_cost' => 80, 'is_active' => true],
            ['name' => 'الدقهلية', 'shipping_cost' => 70, 'is_active' => true],
            ['name' => 'الشرقية', 'shipping_cost' => 70, 'is_active' => true],
            ['name' => 'بورسعيد', 'shipping_cost' => 80, 'is_active' => true],
            ['name' => 'الإسماعيلية', 'shipping_cost' => 80, 'is_active' => true],
            ['name' => 'السويس', 'shipping_cost' => 90, 'is_active' => true],
            ['name' => 'شمال سيناء', 'shipping_cost' => 100, 'is_active' => true],
            ['name' => 'جنوب سيناء', 'shipping_cost' => 100, 'is_active' => true],
            ['name' => 'بني سويف', 'shipping_cost' => 90, 'is_active' => true],
            ['name' => 'منيا', 'shipping_cost' => 100, 'is_active' => true],
            ['name' => 'أسيوط', 'shipping_cost' => 100, 'is_active' => true],
            ['name' => 'سوهاج', 'shipping_cost' => 110, 'is_active' => true],
            ['name' => 'قنا', 'shipping_cost' => 110, 'is_active' => true],
            ['name' => 'الأقصر', 'shipping_cost' => 120, 'is_active' => true],
            ['name' => 'أسوان', 'shipping_cost' => 130, 'is_active' => true],
            ['name' => 'البحر الأحمر', 'shipping_cost' => 120, 'is_active' => true],
            ['name' => 'الوادي الجديد', 'shipping_cost' => 130, 'is_active' => true],
            ['name' => 'مطروح', 'shipping_cost' => 120, 'is_active' => true],
        ];

        foreach ($governorates as $g) {
            Governorate::updateOrCreate(['name' => $g['name']], $g);
        }
    }

    protected function seedCategories(): array
    {
        $electronics = Category::updateOrCreate(
            ['slug' => 'electronics'],
            ['name' => 'إلكترونيات', 'is_active' => true, 'show_in_header' => true, 'order' => 1]
        );

        $phones = Category::updateOrCreate(
            ['slug' => 'phones'],
            ['name' => 'موبايلات', 'parent_id' => $electronics->id, 'is_active' => true, 'order' => 1]
        );

        $laptops = Category::updateOrCreate(
            ['slug' => 'laptops'],
            ['name' => 'لابتوب', 'parent_id' => $electronics->id, 'is_active' => true, 'order' => 2]
        );

        $accessories = Category::updateOrCreate(
            ['slug' => 'accessories'],
            ['name' => 'أكسسوارات', 'parent_id' => $electronics->id, 'is_active' => true, 'order' => 3]
        );

        $clothing = Category::updateOrCreate(
            ['slug' => 'clothing'],
            ['name' => 'ملابس', 'is_active' => true, 'show_in_header' => true, 'order' => 2]
        );

        $menClothing = Category::updateOrCreate(
            ['slug' => 'men-clothing'],
            ['name' => 'رجالي', 'parent_id' => $clothing->id, 'is_active' => true, 'order' => 1]
        );

        $womenClothing = Category::updateOrCreate(
            ['slug' => 'women-clothing'],
            ['name' => 'حريمي', 'parent_id' => $clothing->id, 'is_active' => true, 'order' => 2]
        );

        return [
            'phones' => $phones,
            'laptops' => $laptops,
            'accessories' => $accessories,
            'men' => $menClothing,
            'women' => $womenClothing,
        ];
    }

    protected function seedBrands(): array
    {
        $brandsData = [
            ['name' => 'Samsung', 'slug' => 'samsung', 'description' => 'الإلكترونيات والتكنولوجيا الكورية', 'sort_order' => 1],
            ['name' => 'Apple', 'slug' => 'apple', 'description' => 'منتجات آبل المبتكرة', 'sort_order' => 2],
            ['name' => 'HP', 'slug' => 'hp', 'description' => 'أجهزة الكمبيوتر والطباعة', 'sort_order' => 3],
            ['name' => 'Dell', 'slug' => 'dell', 'description' => 'أجهزة الكمبيوتر والخوادم', 'sort_order' => 4],
            ['name' => 'Lenovo', 'slug' => 'lenovo', 'description' => 'أجهزة الكمبيوتر والتابلت', 'sort_order' => 5],
            ['name' => 'Xiaomi', 'slug' => 'xiaomi', 'description' => 'الإلكترونيات الذكية', 'sort_order' => 6],
            ['name' => 'Sony', 'slug' => 'sony', 'description' => 'الإلكترونيات والألعاب', 'sort_order' => 7],
            ['name' => 'Nike', 'slug' => 'nike', 'description' => 'الملابس والأحذية الرياضية', 'sort_order' => 8],
            ['name' => 'Adidas', 'slug' => 'adidas', 'description' => 'الملابس والأحذية الرياضية', 'sort_order' => 9],
            ['name' => 'Zara', 'slug' => 'zara', 'description' => 'الملابس العصرية', 'sort_order' => 10],
            ['name' => 'H&M', 'slug' => 'hm', 'description' => 'الملابس للجميع', 'sort_order' => 11],
            ['name' => 'Anker', 'slug' => 'anker', 'description' => 'إكسسوارات وشواحن', 'sort_order' => 12],
            ['name' => 'JBL', 'slug' => 'jbl', 'description' => 'السماعات والصوتيات', 'sort_order' => 13],
            ['name' => 'Logitech', 'slug' => 'logitech', 'description' => 'إكسسوارات الكمبيوتر', 'sort_order' => 14],
            ['name' => 'Canon', 'slug' => 'canon', 'description' => 'الكاميرات والطباعة', 'sort_order' => 15],
        ];

        $brands = [];
        foreach ($brandsData as $b) {
            $brands[$b['slug']] = Brand::updateOrCreate(['slug' => $b['slug']], $b);
        }

        return $brands;
    }

    protected function seedAttributes(): array
    {
        $color = Attribute::updateOrCreate(
            ['code' => 'color'],
            ['name' => 'اللون', 'type' => 'color', 'is_filterable' => true, 'sort_order' => 1]
        );

        $colors = [
            ['value' => 'أسود', 'slug' => 'black', 'color_code' => '#000000', 'sort_order' => 1],
            ['value' => 'أبيض', 'slug' => 'white', 'color_code' => '#FFFFFF', 'sort_order' => 2],
            ['value' => 'أحمر', 'slug' => 'red', 'color_code' => '#DC143C', 'sort_order' => 3],
            ['value' => 'أزرق', 'slug' => 'blue', 'color_code' => '#4169E1', 'sort_order' => 4],
            ['value' => 'أخضر', 'slug' => 'green', 'color_code' => '#2E8B57', 'sort_order' => 5],
            ['value' => 'ذهبي', 'slug' => 'gold', 'color_code' => '#DAA520', 'sort_order' => 6],
            ['value' => 'فضي', 'slug' => 'silver', 'color_code' => '#C0C0C0', 'sort_order' => 7],
            ['value' => 'بنفسجي', 'slug' => 'purple', 'color_code' => '#7B2D8E', 'sort_order' => 8],
            ['value' => 'بيج', 'slug' => 'beige', 'color_code' => '#F5F5DC', 'sort_order' => 9],
            ['value' => 'رمادي', 'slug' => 'gray', 'color_code' => '#808080', 'sort_order' => 10],
        ];

        foreach ($colors as $c) {
            $color->values()->updateOrCreate(
                ['attribute_id' => $color->id, 'value' => $c['value']],
                $c
            );
        }

        $size = Attribute::updateOrCreate(
            ['code' => 'size'],
            ['name' => 'المقاس', 'type' => 'select', 'is_filterable' => true, 'sort_order' => 2]
        );

        $sizes = [
            ['value' => 'XS', 'slug' => 'xs', 'sort_order' => 1],
            ['value' => 'S', 'slug' => 's', 'sort_order' => 2],
            ['value' => 'M', 'slug' => 'm', 'sort_order' => 3],
            ['value' => 'L', 'slug' => 'l', 'sort_order' => 4],
            ['value' => 'XL', 'slug' => 'xl', 'sort_order' => 5],
            ['value' => 'XXL', 'slug' => 'xxl', 'sort_order' => 6],
        ];

        foreach ($sizes as $s) {
            $size->values()->updateOrCreate(
                ['attribute_id' => $size->id, 'value' => $s['value']],
                $s
            );
        }

        $material = Attribute::updateOrCreate(
            ['code' => 'material'],
            ['name' => 'الخامة', 'type' => 'select', 'is_filterable' => true, 'sort_order' => 3]
        );

        $materials = [
            ['value' => 'قطن', 'slug' => 'cotton', 'sort_order' => 1],
            ['value' => 'كتان', 'slug' => 'linen', 'sort_order' => 2],
            ['value' => 'صوف', 'slug' => 'wool', 'sort_order' => 3],
            ['value' => 'بوليستر', 'slug' => 'polyester', 'sort_order' => 4],
            ['value' => 'جلد', 'slug' => 'leather', 'sort_order' => 5],
            ['value' => 'حرير', 'slug' => 'silk', 'sort_order' => 6],
            ['value' => 'دنيم', 'slug' => 'denim', 'sort_order' => 7],
        ];

        foreach ($materials as $m) {
            $material->values()->updateOrCreate(
                ['attribute_id' => $material->id, 'value' => $m['value']],
                $m
            );
        }

        $storage = Attribute::updateOrCreate(
            ['code' => 'storage'],
            ['name' => 'سعة التخزين', 'type' => 'select', 'is_filterable' => true, 'sort_order' => 4]
        );

        $storageValues = [
            ['value' => '64 GB', 'slug' => '64gb', 'sort_order' => 1],
            ['value' => '128 GB', 'slug' => '128gb', 'sort_order' => 2],
            ['value' => '256 GB', 'slug' => '256gb', 'sort_order' => 3],
            ['value' => '512 GB', 'slug' => '512gb', 'sort_order' => 4],
            ['value' => '1 TB', 'slug' => '1tb', 'sort_order' => 5],
        ];

        foreach ($storageValues as $sv) {
            $storage->values()->updateOrCreate(
                ['attribute_id' => $storage->id, 'value' => $sv['value']],
                $sv
            );
        }

        $ram = Attribute::updateOrCreate(
            ['code' => 'ram'],
            ['name' => 'الرام', 'type' => 'select', 'is_filterable' => true, 'sort_order' => 5]
        );

        $ramValues = [
            ['value' => '4 GB', 'slug' => '4gb', 'sort_order' => 1],
            ['value' => '6 GB', 'slug' => '6gb', 'sort_order' => 2],
            ['value' => '8 GB', 'slug' => '8gb', 'sort_order' => 3],
            ['value' => '12 GB', 'slug' => '12gb', 'sort_order' => 4],
            ['value' => '16 GB', 'slug' => '16gb', 'sort_order' => 5],
        ];

        foreach ($ramValues as $rv) {
            $ram->values()->updateOrCreate(
                ['attribute_id' => $ram->id, 'value' => $rv['value']],
                $rv
            );
        }

        $connectivity = Attribute::updateOrCreate(
            ['code' => 'connectivity'],
            ['name' => 'نوع الاتصال', 'type' => 'select', 'is_filterable' => true, 'sort_order' => 6]
        );

        $connectivityValues = [
            ['value' => 'USB-C', 'slug' => 'usb-c', 'sort_order' => 1],
            ['value' => 'Lightning', 'slug' => 'lightning', 'sort_order' => 2],
            ['value' => 'بلوتوث', 'slug' => 'bluetooth', 'sort_order' => 3],
            ['value' => 'Wi-Fi', 'slug' => 'wifi', 'sort_order' => 4],
            ['value' => 'HDMI', 'slug' => 'hdmi', 'sort_order' => 5],
        ];

        foreach ($connectivityValues as $cv) {
            $connectivity->values()->updateOrCreate(
                ['attribute_id' => $connectivity->id, 'value' => $cv['value']],
                $cv
            );
        }

        return compact('color', 'size', 'material', 'storage', 'ram', 'connectivity');
    }

    protected function seedPages(): void
    {
        $content = [
            'about' => [
                'title' => 'من نحن',
                'content' => '<h2>منصة التجارة الإلكترونية الأولى في مصر</h2>
<p>نحن متجر إلكتروني متكامل نقدم تشكيلة واسعة من المنتجات الإلكترونية والملابس والإكسسوارات بأعلى جودة وأفضل أسعار.</p>
<p>تأسس متجرنا بهدف توفير تجربة تسوق سهلة وآمنة للعملاء في جميع أنحاء مصر، مع خدمة توصيل سريعة ودعم فني على مدار الساعة.</p>
<h3>رؤيتنا</h3>
<p>أن نكون الخيار الأول للتسوق الإلكتروني في مصر والوطن العربي.</p>
<h3>رسالتنا</h3>
<p>تقديم منتجات أصلية بأسعار تنافسية مع أفضل خدمة عملاء.</p>',
                'sort_order' => 1, 'show_in_header' => true, 'show_in_footer' => true,
            ],
            'privacy-policy' => [
                'title' => 'سياسة الخصوصية',
                'content' => '<h2>سياسة الخصوصية</h2>
<p>نحن ملتزمون بحماية خصوصية معلوماتك الشخصية. توضح هذه السياسة كيفية جمع واستخدام وحماية بياناتك.</p>
<h3>المعلومات التي نجمعها</h3>
<ul>
<li>الاسم والعنوان والبريد الإلكتروني ورقم الهاتف</li>
<li>معلومات الدفع (نستخدم بوابات دفع آمنة)</li>
<li>سجل الطلبات والتصفح</li>
</ul>
<h3>كيف نستخدم معلوماتك</h3>
<ul>
<li>معالجة الطلبات وتوصيلها</li>
<li>تحسين تجربة التسوق</li>
<li>إرسال عروض ترويجية (بموافقتك)</li>
</ul>
<h3>حماية البيانات</h3>
<p>نستخدم أحدث تقنيات التشفير لحماية بياناتك. لا نشارك معلوماتك مع أطراف ثالثة دون موافقتك.</p>',
                'sort_order' => 2, 'show_in_header' => false, 'show_in_footer' => true,
            ],
            'terms-and-conditions' => [
                'title' => 'الشروط والأحكام',
                'content' => '<h2>الشروط والأحكام</h2>
<p>باستخدامك لهذا الموقع فإنك توافق على الشروط والأحكام التالية:</p>
<h3>الحسابات</h3>
<ul>
<li>يجب أن تكون المعلومات التي تقدمها دقيقة وكاملة</li>
<li>أنت مسؤول عن الحفاظ على سرية حسابك</li>
<li>يحق لنا إغلاق أي حساب يخالف الشروط</li>
</ul>
<h3>الطلبات والدفع</h3>
<ul>
<li>جميع الأسعار شاملة ضريبة القيمة المضافة</li>
<li>نحتفظ بالحق في تغيير الأسعار دون إشعار مسبق</li>
<li>الإلغاء مسموح به قبل الشحن فقط</li>
</ul>
<h3>التوصيل</h3>
<ul>
<li>نوصل لجميع محافظات مصر</li>
<li>مدة التوصيل 3-7 أيام عمل</li>
<li>رسوم التوصيل حسب المحافظة</li>
</ul>',
                'sort_order' => 3, 'show_in_header' => false, 'show_in_footer' => true,
            ],
            'return-policy' => [
                'title' => 'سياسة الاستبدال والاسترجاع',
                'content' => '<h2>سياسة الاستبدال والاسترجاع</h2>
<p>نحرص على رضاك التام. إذا لم تكن راضياً عن مشترياتك، يمكنك استبدالها أو استرجاعها خلال 14 يوماً.</p>
<h3>شروط الاسترجاع</h3>
<ul>
<li>المنتج بحالته الأصلية ولم يتم استخدامه</li>
<li>العبوة الأصلية سليمة</li>
<li>إرفاق فاتورة الشراء</li>
<li>المنتجات الإلكترونية تخضع للفحص قبل الموافقة</li>
</ul>
<h3>المنتجات غير القابلة للاسترجاع</h3>
<ul>
<li>الملابس الداخلية</li>
<li>المنتجات الرقمية بعد التحميل</li>
<li>المنتجات المخصصة حسب الطلب</li>
</ul>
<h3>عملية الاسترجاع</h3>
<ol>
<li>تواصل مع خدمة العملاء</li>
<li>قدم طلب الاسترجاع</li>
<li>قم بشحن المنتج إلينا</li>
<li>نقوم برد المبلغ خلال 5-7 أيام عمل</li>
</ol>',
                'sort_order' => 4, 'show_in_header' => false, 'show_in_footer' => true,
            ],
            'shipping-policy' => [
                'title' => 'الشحن والتوصيل',
                'content' => '<h2>سياسة الشحن والتوصيل</h2>
<p>نقدم خدمة التوصيل إلى جميع محافظات مصر مع خيارات متعددة للشحن.</p>
<h3>خيارات التوصيل</h3>
<ul>
<li><strong>توصيل عادي:</strong> 3-7 أيام عمل</li>
<li><strong>توصيل سريع:</strong> 1-2 يوم عمل (للطلب الداخلي)</li>
<li><strong>الشحن للمحافظات:</strong> 5-10 أيام عمل</li>
</ul>
<h3>رسوم الشحن</h3>
<p>تختلف رسوم الشحن حسب المحافظة وتُحتسب عند إتمام الطلب. الشحن مجاني للطلبات التي تتجاوز 2000 جنيه.</p>
<h3>تتبع الطلب</h3>
<p>يمكنك تتبع طلبك من خلال حسابك على الموقع أو عبر التواصل مع خدمة العملاء.</p>',
                'sort_order' => 5, 'show_in_header' => false, 'show_in_footer' => true,
            ],
            'faq' => [
                'title' => 'الأسئلة الشائعة',
                'content' => '<h2>الأسئلة الشائعة</h2>
<div class="faq-item">
<h3>كيف يمكنني إنشاء حساب؟</h3>
<p>يمكنك إنشاء حساب بسهولة من خلال الضغط على زر "تسجيل" وإدخال بياناتك.</p>
</div>
<div class="faq-item">
<h3>ما هي طرق الدفع المتاحة؟</h3>
<p>نقبل الدفع نقداً عند الاستلام، والبطاقات الائتمانية (فيزا، ماستركارد)، والمحافظ الإلكترونية.</p>
</div>
<div class="faq-item">
<h3>هل يمكنني إلغاء الطلب؟</h3>
<p>نعم، يمكنك إلغاء الطلب قبل شحنه. تواصل مع خدمة العملاء للإلغاء.</p>
</div>
<div class="faq-item">
<h3>كم تستغرق معالجة الطلب؟</h3>
<p>نقوم بتجهيز الطلب خلال 24 ساعة من تأكيده، ثم يتم شحنه في اليوم التالي.</p>
</div>
<div class="faq-item">
<h3>هل المنتجات أصلية؟</h3>
<p>نعم، جميع منتجاتنا أصلية ومضمونة، ونوفر ضمان لمدة عام على معظم المنتجات.</p>
</div>',
                'sort_order' => 6, 'show_in_header' => true, 'show_in_footer' => true,
            ],
            'contact' => [
                'title' => 'اتصل بنا',
                'content' => '<h2>اتصل بنا</h2>
<p>نحن هنا لمساعدتك! تواصل معنا عبر أي من القنوات التالية:</p>
<h3>معلومات الاتصال</h3>
<ul>
<li><strong>الهاتف:</strong> 0100 123 4567</li>
<li><strong>واتساب:</strong> 0100 123 4567</li>
<li><strong>البريد الإلكتروني:</strong> support@example.com</li>
<li><strong>العنوان:</strong> القاهرة، مصر</li>
</ul>
<h3>ساعات العمل</h3>
<ul>
<li>السبت - الخميس: 9:00 ص - 9:00 م</li>
<li>الجمعة: 2:00 م - 8:00 م</li>
</ul>
<p>يمكنك أيضاً استخدام نموذج الاتصال بالأسفل وسنرد عليك في أقرب وقت.</p>',
                'sort_order' => 7, 'show_in_header' => true, 'show_in_footer' => true,
            ],
        ];

        foreach ($content as $slug => $data) {
            Page::updateOrCreate(
                ['slug' => $slug],
                ['is_active' => true] + $data
            );
        }
    }

    protected function seedProducts(array $categories, array $brands): void
    {
        // --- Phone with color variation ---
        $phone = Product::updateOrCreate(
            ['slug' => Str::slug('Samsung Galaxy S24 Ultra')],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'sku' => 'SAM-S24U',
                'brand_id' => $brands['samsung']->id,
                'price' => 57999,
                'sale_price' => 54999,
                'quantity' => 50,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'Samsung Galaxy S24 Ultra بذاكرة 256 جيجا، شاشا AMOLED 6.8 بوصة، كاميرا 200 ميجا بيكسل.',
            ]
        );
        $phone->categories()->syncWithoutDetaching([$categories['phones']->id]);

        $colorVariation = $phone->variations()->updateOrCreate(
            ['product_id' => $phone->id, 'name' => 'اللون'],
            ['type' => 'color', 'sort_order' => 1]
        );

        $colors = [
            ['value' => 'أسود', 'color_code' => '#000000'],
            ['value' => 'أبيض', 'color_code' => '#FFFFFF'],
            ['value' => 'بنفسجي', 'color_code' => '#7B2D8E'],
        ];

        $colorOptions = [];
        foreach ($colors as $i => $c) {
            $colorOptions[] = $colorVariation->options()->updateOrCreate(
                ['variation_id' => $colorVariation->id, 'value' => $c['value']],
                ['color_code' => $c['color_code'], 'order' => $i]
            );
        }

        $colorVariants = [
            ['sku' => 'SAM-S24U-BLK', 'qty' => 20, 'price' => 54999],
            ['sku' => 'SAM-S24U-WHT', 'qty' => 15, 'price' => 54999],
            ['sku' => 'SAM-S24U-PRP', 'qty' => 15, 'price' => 55999],
        ];

        foreach ($colorVariants as $i => $v) {
            $variant = $phone->variants()->updateOrCreate(
                ['sku' => $v['sku']],
                [
                    'price' => $v['price'],
                    'quantity' => $v['qty'],
                    'product_id' => $phone->id,
                    'is_active' => true,
                ]
            );
            $variant->options()->syncWithoutDetaching([$colorOptions[$i]->id]);
        }

        // --- Laptop ---
        $laptop = Product::updateOrCreate(
            ['slug' => Str::slug('HP Pavilion 15')],
            [
                'name' => 'HP Pavilion 15',
                'sku' => 'HP-PAV15',
                'brand_id' => $brands['hp']->id,
                'price' => 32999,
                'quantity' => 25,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'لابتوب HP Pavilion 15 بمعالج Intel Core i7 الجيل الـ 13، رام 16 جيجا، سعة 512 SSD.',
            ]
        );
        $laptop->categories()->syncWithoutDetaching([$categories['laptops']->id]);

        // Laptop with RAM variations
        $ramVariation = $laptop->variations()->updateOrCreate(
            ['product_id' => $laptop->id, 'name' => 'الرام'],
            ['type' => 'dropdown', 'sort_order' => 1]
        );

        $ram8 = $ramVariation->options()->updateOrCreate(
            ['variation_id' => $ramVariation->id, 'value' => '8 GB'],
            ['order' => 0]
        );
        $ram16 = $ramVariation->options()->updateOrCreate(
            ['variation_id' => $ramVariation->id, 'value' => '16 GB'],
            ['order' => 1]
        );

        $v8 = $laptop->variants()->updateOrCreate(
            ['sku' => 'HP-PAV15-8G'],
            ['price' => 28999, 'quantity' => 10, 'product_id' => $laptop->id, 'is_active' => true]
        );
        $v8->options()->syncWithoutDetaching([$ram8->id]);

        $v16 = $laptop->variants()->updateOrCreate(
            ['sku' => 'HP-PAV15-16G'],
            ['price' => 32999, 'quantity' => 15, 'product_id' => $laptop->id, 'is_active' => true]
        );
        $v16->options()->syncWithoutDetaching([$ram16->id]);

        // --- Simple product: T-shirt with size variation ---
        $tshirt = Product::updateOrCreate(
            ['slug' => Str::slug('تيشيرت قطن كلاسيك')],
            [
                'name' => 'تيشيرت قطن كلاسيك',
                'sku' => 'TSH-CLC',
                'price' => 399,
                'sale_price' => 299,
                'quantity' => 200,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'تيشيرت قطن 100% متوفر بعدة مقاسات.',
            ]
        );
        $tshirt->categories()->syncWithoutDetaching([$categories['men']->id]);

        $sizeVariation = $tshirt->variations()->updateOrCreate(
            ['product_id' => $tshirt->id, 'name' => 'المقاس'],
            ['type' => 'button', 'sort_order' => 1]
        );

        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $sizeOptions = [];
        foreach ($sizes as $i => $s) {
            $sizeOptions[] = $sizeVariation->options()->updateOrCreate(
                ['variation_id' => $sizeVariation->id, 'value' => $s],
                ['order' => $i]
            );
        }

        foreach ($sizeOptions as $i => $opt) {
            $tshirt->variants()->updateOrCreate(
                ['sku' => 'TSH-CLC-'.$sizes[$i]],
                ['price' => 299, 'quantity' => 40, 'product_id' => $tshirt->id, 'is_active' => true]
            )->options()->syncWithoutDetaching([$opt->id]);
        }

        // --- Simple product without variations ---
        $charger = Product::updateOrCreate(
            ['slug' => Str::slug('شاحن سلكي USB-C 25W')],
            [
                'name' => 'شاحن سلكي USB-C 25W',
                'sku' => 'ACC-CHG25',
                'price' => 199,
                'quantity' => 100,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'شاحن سريع 25 وات بمنفذ USB-C، متوافق مع جميع الأجهزة.',
            ]
        );
        $charger->categories()->syncWithoutDetaching([$categories['accessories']->id]);

        // --- Women clothing: Dress with color + size ---
        $dress = Product::updateOrCreate(
            ['slug' => Str::slug('فستان سهرة ساتان')],
            [
                'name' => 'فستان سهرة ساتان',
                'sku' => 'DRS-SAT',
                'price' => 1299,
                'sale_price' => 999,
                'quantity' => 60,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'فستان سهرة من قماش الساتان، متوفر بعدة ألوان ومقاسات.',
            ]
        );
        $dress->categories()->syncWithoutDetaching([$categories['women']->id]);

        $dressColor = $dress->variations()->updateOrCreate(
            ['product_id' => $dress->id, 'name' => 'اللون'],
            ['type' => 'color', 'sort_order' => 1]
        );

        $dressColors = [
            ['value' => 'أحمر', 'color_code' => '#DC143C'],
            ['value' => 'أسود', 'color_code' => '#000000'],
            ['value' => 'ذهبي', 'color_code' => '#DAA520'],
        ];

        $dressColorOpts = [];
        foreach ($dressColors as $i => $c) {
            $dressColorOpts[] = $dressColor->options()->updateOrCreate(
                ['variation_id' => $dressColor->id, 'value' => $c['value']],
                ['color_code' => $c['color_code'], 'order' => $i]
            );
        }

        $dressSize = $dress->variations()->updateOrCreate(
            ['product_id' => $dress->id, 'name' => 'المقاس'],
            ['type' => 'button', 'sort_order' => 2]
        );

        $dressSizes = ['S', 'M', 'L', 'XL'];
        $dressSizeOpts = [];
        foreach ($dressSizes as $i => $s) {
            $dressSizeOpts[] = $dressSize->options()->updateOrCreate(
                ['variation_id' => $dressSize->id, 'value' => $s],
                ['order' => $i]
            );
        }

        foreach ($dressColorOpts as $colorOpt) {
            foreach ($dressSizeOpts as $sizeOpt) {
                $variant = $dress->variants()->updateOrCreate(
                    ['sku' => 'DRS-SAT-'.Str::slug($colorOpt->value).'-'.$sizeOpt->value],
                    ['price' => 999, 'quantity' => 15, 'product_id' => $dress->id, 'is_active' => true]
                );
                $variant->options()->syncWithoutDetaching([$colorOpt->id, $sizeOpt->id]);
            }
        }

        // --- iPhone with color + storage variations ---
        $iphone = Product::updateOrCreate(
            ['slug' => Str::slug('Apple iPhone 16 Pro Max')],
            [
                'name' => 'Apple iPhone 16 Pro Max',
                'sku' => 'APL-IP16PM',
                'brand_id' => $brands['apple']->id,
                'price' => 64999,
                'sale_price' => 61999,
                'quantity' => 30,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'Apple iPhone 16 Pro Max بشاشة 6.9 بوصة، كاميرا 48 ميجا بيكسل، معالج A18 Pro.',
            ]
        );
        $iphone->categories()->syncWithoutDetaching([$categories['phones']->id]);

        $iphoneColor = $iphone->variations()->updateOrCreate(
            ['product_id' => $iphone->id, 'name' => 'اللون'],
            ['type' => 'color', 'sort_order' => 1]
        );
        $iphoneColors = [
            ['value' => 'أسود', 'color_code' => '#1C1C1E'],
            ['value' => 'أبيض', 'color_code' => '#F5F5F5'],
            ['value' => 'ذهبي', 'color_code' => '#D4AF37'],
        ];
        $iphoneColorOpts = [];
        foreach ($iphoneColors as $i => $c) {
            $iphoneColorOpts[] = $iphoneColor->options()->updateOrCreate(
                ['variation_id' => $iphoneColor->id, 'value' => $c['value']],
                ['color_code' => $c['color_code'], 'order' => $i]
            );
        }

        $iphoneStorage = $iphone->variations()->updateOrCreate(
            ['product_id' => $iphone->id, 'name' => 'سعة التخزين'],
            ['type' => 'button', 'sort_order' => 2]
        );
        $iphoneStorages = ['256 GB', '512 GB', '1 TB'];
        $iphoneStorageOpts = [];
        foreach ($iphoneStorages as $i => $s) {
            $iphoneStorageOpts[] = $iphoneStorage->options()->updateOrCreate(
                ['variation_id' => $iphoneStorage->id, 'value' => $s],
                ['order' => $i]
            );
        }

        foreach ($iphoneColorOpts as $colorOpt) {
            foreach ($iphoneStorageOpts as $i => $storageOpt) {
                $price = match ($iphoneStorages[$i]) {
                    '512 GB' => 67999,
                    '1 TB' => 75999,
                    default => 61999,
                };
                $variant = $iphone->variants()->updateOrCreate(
                    ['sku' => 'APL-IP16PM-'.Str::slug($colorOpt->value).'-'.Str::slug($iphoneStorages[$i])],
                    ['price' => $price, 'quantity' => 10, 'product_id' => $iphone->id, 'is_active' => true]
                );
                $variant->options()->syncWithoutDetaching([$colorOpt->id, $storageOpt->id]);
            }
        }

        // --- Xiaomi Redmi Note 13 ---
        $redmi = Product::updateOrCreate(
            ['slug' => Str::slug('Xiaomi Redmi Note 13 Pro')],
            [
                'name' => 'Xiaomi Redmi Note 13 Pro',
                'sku' => 'XIAO-RN13P',
                'brand_id' => $brands['xiaomi']->id,
                'price' => 14999,
                'sale_price' => 12999,
                'quantity' => 40,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'Xiaomi Redmi Note 13 Pro بكاميرا 200 ميجا بيكسل، شاشة AMOLED 6.67 بوصة.',
            ]
        );
        $redmi->categories()->syncWithoutDetaching([$categories['phones']->id]);

        // --- Dell XPS Laptop ---
        $dellXps = Product::updateOrCreate(
            ['slug' => Str::slug('Dell XPS 15')],
            [
                'name' => 'Dell XPS 15',
                'sku' => 'DELL-XPS15',
                'brand_id' => $brands['dell']->id,
                'price' => 45999,
                'sale_price' => 42999,
                'quantity' => 15,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'لابتوب Dell XPS 15 بمعالج Intel Core i9، رام 32 جيجا، شاشة OLED 4K.',
            ]
        );
        $dellXps->categories()->syncWithoutDetaching([$categories['laptops']->id]);

        // --- Lenovo ThinkPad ---
        $thinkpad = Product::updateOrCreate(
            ['slug' => Str::slug('Lenovo ThinkPad X1 Carbon')],
            [
                'name' => 'Lenovo ThinkPad X1 Carbon',
                'sku' => 'LEN-TPX1C',
                'brand_id' => $brands['lenovo']->id,
                'price' => 38999,
                'quantity' => 10,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'لابتوب Lenovo ThinkPad X1 Carbon خفيف الوزن، معالج Intel Core i7، رام 16 جيجا.',
            ]
        );
        $thinkpad->categories()->syncWithoutDetaching([$categories['laptops']->id]);

        // --- Nike Sneakers ---
        $nike = Product::updateOrCreate(
            ['slug' => Str::slug('حذاء Nike Air Max 270 رياضي')],
            [
                'name' => 'حذاء Nike Air Max 270 رياضي',
                'sku' => 'NIK-AM270',
                'brand_id' => $brands['nike']->id,
                'price' => 4299,
                'sale_price' => 3599,
                'quantity' => 80,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'حذاء Nike Air Max 270 رياضي للرجال، نعل هوائي للراحة الفائقة.',
            ]
        );
        $nike->categories()->syncWithoutDetaching([$categories['men']->id]);

        $nikeSize = $nike->variations()->updateOrCreate(
            ['product_id' => $nike->id, 'name' => 'المقاس'],
            ['type' => 'button', 'sort_order' => 1]
        );
        $nikeSizes = ['39', '40', '41', '42', '43', '44'];
        foreach ($nikeSizes as $i => $s) {
            $opt = $nikeSize->options()->updateOrCreate(
                ['variation_id' => $nikeSize->id, 'value' => $s],
                ['order' => $i]
            );
            $nike->variants()->updateOrCreate(
                ['sku' => 'NIK-AM270-'.$s],
                ['price' => 3599, 'quantity' => 10, 'product_id' => $nike->id, 'is_active' => true]
            )->options()->syncWithoutDetaching([$opt->id]);
        }

        // --- Adidas T-shirt ---
        $adidasTshirt = Product::updateOrCreate(
            ['slug' => Str::slug('تيشيرت Adidas رياضي')],
            [
                'name' => 'تيشيرت Adidas رياضي',
                'sku' => 'ADI-TSH',
                'brand_id' => $brands['adidas']->id,
                'price' => 899,
                'sale_price' => 699,
                'quantity' => 150,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'تيشيرت Adidas رياضي بقماش قطني مريح، مناسب للرياضة والاستخدام اليومي.',
            ]
        );
        $adidasTshirt->categories()->syncWithoutDetaching([$categories['men']->id]);

        $adidasSize = $adidasTshirt->variations()->updateOrCreate(
            ['product_id' => $adidasTshirt->id, 'name' => 'المقاس'],
            ['type' => 'button', 'sort_order' => 1]
        );
        foreach (['S', 'M', 'L', 'XL', 'XXL'] as $i => $s) {
            $opt = $adidasSize->options()->updateOrCreate(
                ['variation_id' => $adidasSize->id, 'value' => $s],
                ['order' => $i]
            );
            $adidasTshirt->variants()->updateOrCreate(
                ['sku' => 'ADI-TSH-'.$s],
                ['price' => 699, 'quantity' => 30, 'product_id' => $adidasTshirt->id, 'is_active' => true]
            )->options()->syncWithoutDetaching([$opt->id]);
        }

        // --- JBL Speaker ---
        $jbl = Product::updateOrCreate(
            ['slug' => Str::slug('سماعة JBL Flip 6 بلوتوث')],
            [
                'name' => 'سماعة JBL Flip 6 بلوتوث',
                'sku' => 'JBL-FLIP6',
                'brand_id' => $brands['jbl']->id,
                'price' => 2999,
                'sale_price' => 2599,
                'quantity' => 60,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'سماعة JBL Flip 6 محمولة مع بلوتوث 5.1، مقاومة للماء، صوت جهوري عالي الجودة.',
            ]
        );
        $jbl->categories()->syncWithoutDetaching([$categories['accessories']->id]);

        // --- Anker Power Bank ---
        $anker = Product::updateOrCreate(
            ['slug' => Str::slug('باور بانك Anker 20000mAh')],
            [
                'name' => 'باور بانك Anker 20000mAh',
                'sku' => 'ANK-PB20',
                'brand_id' => $brands['anker']->id,
                'price' => 1299,
                'sale_price' => 1099,
                'quantity' => 75,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'باور بانك Anker بسعة 20000 مللي أمبير، شحن سريع PowerIQ، منفذي USB.',
            ]
        );
        $anker->categories()->syncWithoutDetaching([$categories['accessories']->id]);

        // --- Logitech Mouse ---
        $logitech = Product::updateOrCreate(
            ['slug' => Str::slug('ماوس Logitech MX Master 3S')],
            [
                'name' => 'ماوس Logitech MX Master 3S',
                'sku' => 'LOG-MX3S',
                'brand_id' => $brands['logitech']->id,
                'price' => 3499,
                'quantity' => 40,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'ماوس Logitech MX Master 3S لاسلكي، حساسية 8000 DPI، شحن USB-C.',
            ]
        );
        $logitech->categories()->syncWithoutDetaching([$categories['accessories']->id]);

        // --- Sony Headphones ---
        $sony = Product::updateOrCreate(
            ['slug' => Str::slug('سماعة Sony WH-1000XM5')],
            [
                'name' => 'سماعة Sony WH-1000XM5',
                'sku' => 'SON-WH1000XM5',
                'brand_id' => $brands['sony']->id,
                'price' => 8999,
                'sale_price' => 7999,
                'quantity' => 25,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'سماعة Sony WH-1000XM5 لاسلكية مع إلغاء الضوضاء النشط، جودة صوت عالية.',
            ]
        );
        $sony->categories()->syncWithoutDetaching([$categories['accessories']->id]);

        // --- Zara Jacket ---
        $zara = Product::updateOrCreate(
            ['slug' => Str::slug('جاكيت Zara شتوي رجالي')],
            [
                'name' => 'جاكيت Zara شتوي رجالي',
                'sku' => 'ZAR-JKT',
                'brand_id' => $brands['zara']->id,
                'price' => 2499,
                'sale_price' => 1999,
                'quantity' => 45,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'جاكيت Zara شتوي رجالي بتصميم عصري، قماش عالي الجودة مبطن للحماية من البرد.',
            ]
        );
        $zara->categories()->syncWithoutDetaching([$categories['men']->id]);

        $zaraSize = $zara->variations()->updateOrCreate(
            ['product_id' => $zara->id, 'name' => 'المقاس'],
            ['type' => 'button', 'sort_order' => 1]
        );
        foreach (['S', 'M', 'L', 'XL'] as $i => $s) {
            $opt = $zaraSize->options()->updateOrCreate(
                ['variation_id' => $zaraSize->id, 'value' => $s],
                ['order' => $i]
            );
            $zara->variants()->updateOrCreate(
                ['sku' => 'ZAR-JKT-'.$s],
                ['price' => 1999, 'quantity' => 10, 'product_id' => $zara->id, 'is_active' => true]
            )->options()->syncWithoutDetaching([$opt->id]);
        }

        // --- Women's Zara Dress ---
        $zaraDress = Product::updateOrCreate(
            ['slug' => Str::slug('فستان Zara صيفي')],
            [
                'name' => 'فستان Zara صيفي',
                'sku' => 'ZAR-DRS',
                'brand_id' => $brands['zara']->id,
                'price' => 1599,
                'sale_price' => 1299,
                'quantity' => 50,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'فستان Zara صيفي بقماش خفيف وناعم، مناسب للإطلالات النهارية.',
            ]
        );
        $zaraDress->categories()->syncWithoutDetaching([$categories['women']->id]);

        $zaraDressSize = $zaraDress->variations()->updateOrCreate(
            ['product_id' => $zaraDress->id, 'name' => 'المقاس'],
            ['type' => 'button', 'sort_order' => 1]
        );
        foreach (['S', 'M', 'L'] as $i => $s) {
            $opt = $zaraDressSize->options()->updateOrCreate(
                ['variation_id' => $zaraDressSize->id, 'value' => $s],
                ['order' => $i]
            );
            $zaraDress->variants()->updateOrCreate(
                ['sku' => 'ZAR-DRS-'.$s],
                ['price' => 1299, 'quantity' => 15, 'product_id' => $zaraDress->id, 'is_active' => true]
            )->options()->syncWithoutDetaching([$opt->id]);
        }

        // --- H&M Women's Blouse ---
        $hmBlouse = Product::updateOrCreate(
            ['slug' => Str::slug('بلوزة H&M نسائية')],
            [
                'name' => 'بلوزة H&M نسائية',
                'sku' => 'HM-BLS',
                'brand_id' => $brands['hm']->id,
                'price' => 599,
                'sale_price' => 449,
                'quantity' => 100,
                'track_stock' => true,
                'type' => 'physical',
                'is_active' => true,
                'description' => 'بلوزة H&M نسائية بقماش قطني ناعم، متوفرة بعدة ألوان عصرية.',
            ]
        );
        $hmBlouse->categories()->syncWithoutDetaching([$categories['women']->id]);

        $hmColor = $hmBlouse->variations()->updateOrCreate(
            ['product_id' => $hmBlouse->id, 'name' => 'اللون'],
            ['type' => 'color', 'sort_order' => 1]
        );
        $hmColors = [
            ['value' => 'أحمر', 'color_code' => '#DC143C'],
            ['value' => 'أزرق', 'color_code' => '#4169E1'],
            ['value' => 'بيج', 'color_code' => '#F5F5DC'],
        ];
        $hmColorOpts = [];
        foreach ($hmColors as $i => $c) {
            $hmColorOpts[] = $hmColor->options()->updateOrCreate(
                ['variation_id' => $hmColor->id, 'value' => $c['value']],
                ['color_code' => $c['color_code'], 'order' => $i]
            );
        }

        $hmSize = $hmBlouse->variations()->updateOrCreate(
            ['product_id' => $hmBlouse->id, 'name' => 'المقاس'],
            ['type' => 'button', 'sort_order' => 2]
        );
        $hmSizes = ['S', 'M', 'L', 'XL'];
        $hmSizeOpts = [];
        foreach ($hmSizes as $i => $s) {
            $hmSizeOpts[] = $hmSize->options()->updateOrCreate(
                ['variation_id' => $hmSize->id, 'value' => $s],
                ['order' => $i]
            );
        }

        foreach ($hmColorOpts as $colorOpt) {
            foreach ($hmSizeOpts as $sizeOpt) {
                $variant = $hmBlouse->variants()->updateOrCreate(
                    ['sku' => 'HM-BLS-'.Str::slug($colorOpt->value).'-'.$sizeOpt->value],
                    ['price' => 449, 'quantity' => 8, 'product_id' => $hmBlouse->id, 'is_active' => true]
                );
                $variant->options()->syncWithoutDetaching([$colorOpt->id, $sizeOpt->id]);
            }
        }
    }

    protected function seedDemoUsers(): array
    {
        $users = [];

        $users['ahmed'] = TenantUser::updateOrCreate(['email' => 'ahmed@example.com'], [
            'name' => 'أحمد محمد',
            'phone' => '01000000001',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'is_verified' => true,
        ]);

        $users['sara'] = TenantUser::updateOrCreate(['email' => 'sara@example.com'], [
            'name' => 'سارة علي',
            'phone' => '01000000002',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'is_verified' => true,
        ]);

        return $users;
    }

    protected function seedCustomers(array $users): void
    {
        $ahmed = Customer::updateOrCreate(['user_id' => $users['ahmed']->id], [
            'name' => 'أحمد محمد',
        ]);

        CustomerContact::updateOrCreate([
            'customer_id' => $ahmed->id,
            'type' => 'email',
            'value' => 'ahmed@example.com',
        ], [
            'verified_at' => now(),
            'is_primary' => true,
        ]);

        CustomerContact::updateOrCreate([
            'customer_id' => $ahmed->id,
            'type' => 'phone',
            'value' => '01000000001',
        ], [
            'verified_at' => now(),
            'is_primary' => true,
        ]);

        $sara = Customer::updateOrCreate(['user_id' => $users['sara']->id], [
            'name' => 'سارة علي',
        ]);

        CustomerContact::updateOrCreate([
            'customer_id' => $sara->id,
            'type' => 'email',
            'value' => 'sara@example.com',
        ], [
            'verified_at' => now(),
            'is_primary' => true,
        ]);

        CustomerContact::updateOrCreate([
            'customer_id' => $sara->id,
            'type' => 'phone',
            'value' => '01000000002',
        ], [
            'verified_at' => now(),
            'is_primary' => true,
        ]);
    }

    protected function seedReviews(): void
    {
        $products = Product::where('is_active', true)->get();
        $users = TenantUser::where('email', 'like', '%@example.com')->get();

        if ($products->isEmpty() || $users->isEmpty()) {
            return;
        }

        $reviewsData = [
            ['rating' => 5, 'title' => 'منتج رائع', 'comment' => 'جودة ممتازة وسعر مناسب. أنصح به الجميع.'],
            ['rating' => 4, 'title' => 'جيد جداً', 'comment' => 'منتج جيد جداً ولكن التوصيل كان بطيئاً قليلاً.'],
            ['rating' => 5, 'title' => 'أفضل من المتوقع', 'comment' => 'المنتج أفضل من الصور، خامات عالية الجودة.'],
            ['rating' => 3, 'title' => 'متوسط', 'comment' => 'مقبول بالنسبة للسعر، لكن كان توقعي أعلى.'],
            ['rating' => 4, 'title' => 'أنصح به', 'comment' => 'تجربة شراء ممتازة، المنتج مطابق للوصف.'],
        ];

        foreach ($products as $i => $product) {
            $user = $users[$i % $users->count()];
            $review = $reviewsData[$i % count($reviewsData)];

            Review::updateOrCreate([
                'user_id' => $user->id,
                'reviewable_type' => Product::class,
                'reviewable_id' => $product->id,
            ], [
                'rating' => $review['rating'],
                'title' => $review['title'],
                'comment' => $review['comment'],
                'is_featured' => $review['rating'] >= 4,
                'is_approved' => true,
            ]);
        }
    }

    protected function seedContacts(): void
    {
        Contact::updateOrCreate(['email' => 'mahmoud@example.com'], [
            'name' => 'محمود سامي',
            'phone' => '01234567890',
            'message' => 'السلام عليكم، أريد الاستفسار عن توفر منتج Samsung Galaxy S24 Ultra باللون الأبيض.',
            'status' => 'completed',
            'read_at' => now(),
        ]);

        Contact::updateOrCreate(['email' => 'nourhan@example.com'], [
            'name' => 'نورهان أحمد',
            'phone' => '01123456789',
            'message' => 'هل لديكم خدمة التوصيل إلى مدينة الغردقة؟ وكم تكلفة الشحن؟',
            'status' => 'on_progress',
            'read_at' => now(),
        ]);

        Contact::updateOrCreate(['email' => 'kareem@example.com'], [
            'name' => 'كريم حسن',
            'phone' => '01098765432',
            'message' => 'أريد استرجاع طلب رقم 12345، كيف يمكنني ذلك؟',
            'status' => 'pending',
            'read_at' => null,
        ]);

        Contact::updateOrCreate(['email' => 'dina@example.com'], [
            'name' => 'دينا يوسف',
            'phone' => '01555555555',
            'message' => 'شكراً لكم على خدمتكم الممتازة، وصل الطلب بسرعة والمنتج رائع!',
            'status' => 'completed',
            'read_at' => now(),
        ]);
    }

    protected function seedCoupons(): void
    {
        Coupon::updateOrCreate(['code' => 'WELCOME10'], [
            'type' => 'percentage',
            'value' => 10,
            'minimum_order_amount' => 500,
            'maximum_discount_amount' => 200,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'is_active' => true,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        Coupon::updateOrCreate(['code' => 'FLAT50'], [
            'type' => 'fixed',
            'value' => 50,
            'minimum_order_amount' => 300,
            'usage_limit' => null,
            'per_user_limit' => null,
            'is_active' => true,
            'starts_at' => now(),
            'expires_at' => now()->addMonths(3),
        ]);

        Coupon::updateOrCreate(['code' => 'VIP20'], [
            'type' => 'percentage',
            'value' => 20,
            'minimum_order_amount' => 1000,
            'maximum_discount_amount' => 500,
            'usage_limit' => 50,
            'per_user_limit' => 2,
            'is_active' => true,
            'starts_at' => now(),
            'expires_at' => now()->addMonths(6),
        ]);

        Coupon::updateOrCreate(['code' => 'EXPIRED5'], [
            'type' => 'fixed',
            'value' => 5,
            'minimum_order_amount' => 0,
            'usage_limit' => null,
            'per_user_limit' => null,
            'is_active' => false,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
        ]);
    }
}
