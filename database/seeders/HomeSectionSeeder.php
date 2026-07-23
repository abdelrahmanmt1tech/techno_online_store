<?php

namespace Database\Seeders;

use App\Models\Tenant\HomeSection;
use Illuminate\Database\Seeder;

class HomeSectionSeeder extends Seeder
{
    public function run(): void
    {
        HomeSection::create([
            'type' => 'hero',
            'content' => [
                'title' => 'أحدث المنتجات بأسعار مميزة',
                'subtitle' => 'اكتشف تشكيلتنا الواسعة من الإلكترونيات والملابس',
                'button_text' => 'تسوق الآن',
                'button_url' => '/products',
                'image' => null,
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        HomeSection::create([
            'type' => 'categories',
            'content' => [
                'title' => 'تصفح حسب التصنيف',
                'category_ids' => [],
            ],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        HomeSection::create([
            'type' => 'new_arrivals',
            'content' => [
                'title' => 'وصل حديثاً',
                'subtitle' => 'أحدث المنتجات المضافة لمتجرنا',
                'products_count' => 8,
            ],
            'sort_order' => 3,
            'is_active' => true,
        ]);

        HomeSection::create([
            'type' => 'best_sellers',
            'content' => [
                'title' => 'الأكثر مبيعاً',
                'subtitle' => 'المنتجات الأكثر رضاً عند عملائنا',
                'products_count' => 8,
            ],
            'sort_order' => 4,
            'is_active' => true,
        ]);

        HomeSection::create([
            'type' => 'deals',
            'content' => [
                'title' => 'عروض لا تُفوّت',
                'subtitle' => 'خصومات حصرية على منتجات مختارة',
                'products_count' => 8,
            ],
            'sort_order' => 5,
            'is_active' => true,
        ]);

        HomeSection::create([
            'type' => 'testimonials',
            'content' => [
                'title' => 'ماذا يقول عملاؤنا',
                'subtitle' => 'تجارب حقيقية من عملاء سعداء',
                'items' => [
                    [
                        'customer_name' => 'أحمد محمد',
                        'review' => 'تجربة تسوق ممتازة، المنتجات أصلية والتوصيل سريع.',
                        'customer_image' => null,
                        'rating' => 5,
                    ],
                    [
                        'customer_name' => 'سارة علي',
                        'review' => 'خدمة عملاء رائعة، ساعدوني في اختيار المنتج المناسب.',
                        'customer_image' => null,
                        'rating' => 5,
                    ],
                ],
            ],
            'sort_order' => 6,
            'is_active' => true,
        ]);
    }
}
