<?php

namespace Database\Seeders;

use App\Models\Tenant\Category;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Governorate;
use App\Models\Tenant\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGovernorates();
        $categories = $this->seedCategories();
        $this->seedProducts($categories);
        $this->seedCoupons();
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
            Governorate::create($g);
        }
    }

    protected function seedCategories(): array
    {
        $electronics = Category::create([
            'name' => 'إلكترونيات',
            'slug' => 'electronics',
            'is_active' => true,
            'show_in_header' => true,
            'order' => 1,
        ]);

        $phones = Category::create([
            'name' => 'موبايلات',
            'slug' => 'phones',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'order' => 1,
        ]);

        $laptops = Category::create([
            'name' => 'لابتوب',
            'slug' => 'laptops',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'order' => 2,
        ]);

        $accessories = Category::create([
            'name' => 'أكسسوارات',
            'slug' => 'accessories',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'order' => 3,
        ]);

        $clothing = Category::create([
            'name' => 'ملابس',
            'slug' => 'clothing',
            'is_active' => true,
            'show_in_header' => true,
            'order' => 2,
        ]);

        $menClothing = Category::create([
            'name' => 'رجالي',
            'slug' => 'men-clothing',
            'parent_id' => $clothing->id,
            'is_active' => true,
            'order' => 1,
        ]);

        $womenClothing = Category::create([
            'name' => 'حريمي',
            'slug' => 'women-clothing',
            'parent_id' => $clothing->id,
            'is_active' => true,
            'order' => 2,
        ]);

        return [
            'phones' => $phones,
            'laptops' => $laptops,
            'accessories' => $accessories,
            'men' => $menClothing,
            'women' => $womenClothing,
        ];
    }

    protected function seedProducts(array $categories): void
    {
        // --- Phone with color variation ---
        $phone = Product::create([
            'name' => 'Samsung Galaxy S24 Ultra',
            'slug' => Str::slug('Samsung Galaxy S24 Ultra'),
            'sku' => 'SAM-S24U',
            'price' => 57999,
            'sale_price' => 54999,
            'quantity' => 50,
            'track_stock' => true,
            'type' => 'physical',
            'is_active' => true,
            'description' => 'Samsung Galaxy S24 Ultra بذاكرة 256 جيجا، شاشا AMOLED 6.8 بوصة، كاميرا 200 ميجا بيكسل.',
        ]);
        $phone->categories()->attach($categories['phones']->id);

        $colorVariation = $phone->variations()->create([
            'name' => 'اللون',
            'type' => 'color',
            'sort_order' => 1,
        ]);

        $colors = [
            ['value' => 'أسود', 'color_code' => '#000000'],
            ['value' => 'أبيض', 'color_code' => '#FFFFFF'],
            ['value' => 'بنفسجي', 'color_code' => '#7B2D8E'],
        ];

        $colorOptions = [];
        foreach ($colors as $i => $c) {
            $colorOptions[] = $colorVariation->options()->create([
                'value' => $c['value'],
                'color_code' => $c['color_code'],
                'order' => $i,
            ]);
        }

        $colorVariants = [
            ['sku' => 'SAM-S24U-BLK', 'qty' => 20, 'price' => 54999],
            ['sku' => 'SAM-S24U-WHT', 'qty' => 15, 'price' => 54999],
            ['sku' => 'SAM-S24U-PRP', 'qty' => 15, 'price' => 55999],
        ];

        foreach ($colorVariants as $i => $v) {
            $variant = $phone->variants()->create([
                'price' => $v['price'],
                'quantity' => $v['qty'],
                'sku' => $v['sku'],
                'is_active' => true,
            ]);
            $variant->options()->attach($colorOptions[$i]->id);
        }

        // --- Laptop ---
        $laptop = Product::create([
            'name' => 'HP Pavilion 15',
            'slug' => Str::slug('HP Pavilion 15'),
            'sku' => 'HP-PAV15',
            'price' => 32999,
            'quantity' => 25,
            'track_stock' => true,
            'type' => 'physical',
            'is_active' => true,
            'description' => 'لابتوب HP Pavilion 15 بمعالج Intel Core i7 الجيل الـ 13، رام 16 جيجا، سعة 512 SSD.',
        ]);
        $laptop->categories()->attach($categories['laptops']->id);

        // Laptop with RAM + Storage variations
        $ramVariation = $laptop->variations()->create([
            'name' => 'الرام',
            'type' => 'dropdown',
            'sort_order' => 1,
        ]);

        $ram8 = $ramVariation->options()->create(['value' => '8 GB', 'order' => 0]);
        $ram16 = $ramVariation->options()->create(['value' => '16 GB', 'order' => 1]);

        $v8 = $laptop->variants()->create(['price' => 28999, 'quantity' => 10, 'sku' => 'HP-PAV15-8G', 'is_active' => true]);
        $v8->options()->attach($ram8->id);

        $v16 = $laptop->variants()->create(['price' => 32999, 'quantity' => 15, 'sku' => 'HP-PAV15-16G', 'is_active' => true]);
        $v16->options()->attach($ram16->id);

        // --- Simple product: T-shirt with size variation ---
        $tshirt = Product::create([
            'name' => 'تيشيرت قطن كلاسيك',
            'slug' => Str::slug('تيشيرت قطن كلاسيك'),
            'sku' => 'TSH-CLC',
            'price' => 399,
            'sale_price' => 299,
            'quantity' => 200,
            'track_stock' => true,
            'type' => 'physical',
            'is_active' => true,
            'description' => 'تيشيرت قطن 100% متوفر بعدة مقاسات.',
        ]);
        $tshirt->categories()->attach($categories['men']->id);

        $sizeVariation = $tshirt->variations()->create([
            'name' => 'المقاس',
            'type' => 'button',
            'sort_order' => 1,
        ]);

        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $sizeOptions = [];
        foreach ($sizes as $i => $s) {
            $sizeOptions[] = $sizeVariation->options()->create(['value' => $s, 'order' => $i]);
        }

        foreach ($sizeOptions as $i => $opt) {
            $tshirt->variants()->create([
                'price' => 299,
                'quantity' => 40,
                'sku' => 'TSH-CLC-'.$sizes[$i],
                'is_active' => true,
            ])->options()->attach($opt->id);
        }

        // --- Simple product without variations ---
        $charger = Product::create([
            'name' => 'شاحن سلكي USB-C 25W',
            'slug' => Str::slug('شاحن سلكي USB-C 25W'),
            'sku' => 'ACC-CHG25',
            'price' => 199,
            'quantity' => 100,
            'track_stock' => true,
            'type' => 'physical',
            'is_active' => true,
            'description' => 'شاحن سريع 25 وات بمنفذ USB-C، متوافق مع جميع الأجهزة.',
        ]);
        $charger->categories()->attach($categories['accessories']->id);

        // --- Women clothing: Dress with color + size ---
        $dress = Product::create([
            'name' => 'فستان سهرة ساتان',
            'slug' => Str::slug('فستان سهرة ساتان'),
            'sku' => 'DRS-SAT',
            'price' => 1299,
            'sale_price' => 999,
            'quantity' => 60,
            'track_stock' => true,
            'type' => 'physical',
            'is_active' => true,
            'description' => 'فستان سهرة من قماش الساتان، متوفر بعدة ألوان ومقاسات.',
        ]);
        $dress->categories()->attach($categories['women']->id);

        $dressColor = $dress->variations()->create([
            'name' => 'اللون',
            'type' => 'color',
            'sort_order' => 1,
        ]);

        $dressColors = [
            ['value' => 'أحمر', 'color_code' => '#DC143C'],
            ['value' => 'أسود', 'color_code' => '#000000'],
            ['value' => 'ذهبي', 'color_code' => '#DAA520'],
        ];

        $dressColorOpts = [];
        foreach ($dressColors as $i => $c) {
            $dressColorOpts[] = $dressColor->options()->create([
                'value' => $c['value'],
                'color_code' => $c['color_code'],
                'order' => $i,
            ]);
        }

        $dressSize = $dress->variations()->create([
            'name' => 'المقاس',
            'type' => 'button',
            'sort_order' => 2,
        ]);

        $dressSizes = ['S', 'M', 'L', 'XL'];
        $dressSizeOpts = [];
        foreach ($dressSizes as $i => $s) {
            $dressSizeOpts[] = $dressSize->options()->create(['value' => $s, 'order' => $i]);
        }

        foreach ($dressColorOpts as $colorOpt) {
            foreach ($dressSizeOpts as $sizeOpt) {
                $variant = $dress->variants()->create([
                    'price' => 999,
                    'quantity' => 15,
                    'sku' => 'DRS-SAT-'.Str::slug($colorOpt->value).'-'.$sizeOpt->value,
                    'is_active' => true,
                ]);
                $variant->options()->attach([$colorOpt->id, $sizeOpt->id]);
            }
        }
    }

    protected function seedCoupons(): void
    {
        Coupon::create([
            'code' => 'WELCOME10',
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

        Coupon::create([
            'code' => 'FLAT50',
            'type' => 'fixed',
            'value' => 50,
            'minimum_order_amount' => 300,
            'usage_limit' => null,
            'per_user_limit' => null,
            'is_active' => true,
            'starts_at' => now(),
            'expires_at' => now()->addMonths(3),
        ]);

        Coupon::create([
            'code' => 'VIP20',
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

        Coupon::create([
            'code' => 'EXPIRED5',
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
