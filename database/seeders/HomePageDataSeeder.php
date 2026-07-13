<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class HomePageDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Settings (pages from app/Filament/Pages/) ──

        $settings = [
            // IntroSettings
            'intro_section_active' => '1',
            'intro_title_ar' => 'متجر تكنو — وجهتك للتسوق الذكي',
            'intro_title_en' => 'Techno Store — Your Smart Shopping Destination',
            'intro_description_ar' => 'اكتشف أحدث المنتجات التقنية مع أفضل العروض والتوصيل السريع في جميع أنحاء المملكة.',
            'intro_description_en' => 'Discover the latest tech products with the best deals and fast delivery across the kingdom.',
            'intro_image' => '',
            'intro_link' => '/shop',

            // AboutSettings
            'about_section_active' => '1',
            'about_small_title_ar' => 'من نحن',
            'about_small_title_en' => 'Who We Are',
            'about_main_title_ar' => 'نقدم أفضل الحلول التقنية لعملائنا',
            'about_main_title_en' => 'We Provide The Best Tech Solutions For Our Clients',
            'about_description_ar' => 'متجر تكنو هو منصة رائدة في مجال التجارة الإلكترونية نقدم أحدث الأجهزة والمنتجات التقنية بأسعار تنافسية وخدمة عملاء متميزة.',
            'about_description_en' => 'Techno Store is a leading e-commerce platform offering the latest tech devices and products at competitive prices with exceptional customer service.',
            'about_features' => json_encode([
                ['title_ar' => 'جودة عالية', 'title_en' => 'High Quality', 'description_ar' => 'نضمن لك منتجات أصلية 100%', 'description_en' => 'We guarantee 100% genuine products', 'image' => ''],
                ['title_ar' => 'شحن سريع', 'title_en' => 'Fast Shipping', 'description_ar' => 'توصيل خلال 24 ساعة', 'description_en' => 'Delivery within 24 hours', 'image' => ''],
                ['title_ar' => 'دعم فني', 'title_en' => 'Technical Support', 'description_ar' => 'فريق دعم متاح 24/7', 'description_en' => 'Support team available 24/7', 'image' => ''],
            ]),

            // StatisticsSettings
            'statistics_section_active' => '1',
            'statistics_title_ar' => 'إحصائياتنا',
            'statistics_title_en' => 'Our Statistics',
            'statistics_description_ar' => 'أرقام تعكس ثقة عملائنا بنا',
            'statistics_description_en' => 'Numbers that reflect our clients\' trust',
            'statistics_items' => json_encode([
                ['title_ar' => 'عميل سعيد', 'title_en' => 'Happy Clients', 'value' => 15000],
                ['title_ar' => 'منتج', 'title_en' => 'Products', 'value' => 8500],
                ['title_ar' => 'شحنة ناجحة', 'title_en' => 'Successful Deliveries', 'value' => 50000],
                ['title_ar' => 'تقييم إيجابي', 'title_en' => 'Positive Reviews', 'value' => 98],
            ]),

            // AiServicesSettings
            'ai_services_section_active' => '1',
            'ai_services_small_title_ar' => 'خدمات الذكاء الاصطناعي',
            'ai_services_small_title_en' => 'AI Services',
            'ai_services_main_title_ar' => 'حلول ذكية لتجربة تسوق أفضل',
            'ai_services_main_title_en' => 'Smart Solutions For A Better Shopping Experience',
            'ai_services_description_ar' => 'نوظف أحدث تقنيات الذكاء الاصطناعي لتحسين تجربة التسوق عبر متجرنا.',
            'ai_services_description_en' => 'We leverage the latest AI technologies to enhance your shopping experience in our store.',
            'ai_services_items' => json_encode([
                ['title_ar' => 'توصيات ذكية', 'title_en' => 'Smart Recommendations', 'description_ar' => 'نظام توصيات يعتمد على تفضيلاتك', 'description_en' => 'Recommendation system based on your preferences', 'image' => ''],
                ['title_ar' => 'بحث صوتي', 'title_en' => 'Voice Search', 'description_ar' => 'ابحث عن المنتجات بصوتك', 'description_en' => 'Search for products with your voice', 'image' => ''],
                ['title_ar' => 'دردشة ذكية', 'title_en' => 'Smart Chat', 'description_ar' => 'مساعد ذكي يجيب على استفساراتك', 'description_en' => 'Smart assistant answering your inquiries', 'image' => ''],
            ]),

            // Plans section titles
            'plans_title_ar' => 'خطط الأسعار',
            'plans_title_en' => 'Pricing Plans',
            'plans_description_ar' => 'اختر الخطة المناسبة لاحتياجات متجرك',
            'plans_description_en' => 'Choose the right plan for your store\'s needs',

            // PaymentGatewaysSettings
            'payment_gateways_section_active' => '1',
            'payment_gateways_small_title_ar' => 'بوابات الدفع',
            'payment_gateways_small_title_en' => 'Payment Gateways',
            'payment_gateways_main_title_ar' => 'طرق دفع آمنة ومتنوعة',
            'payment_gateways_main_title_en' => 'Secure & Diverse Payment Methods',
            'payment_gateways_description_ar' => 'نوفر لك مجموعة من بوابات الدفع الإلكتروني الآمنة لتجربة شراء سلسة.',
            'payment_gateways_description_en' => 'We provide a range of secure online payment gateways for a smooth purchasing experience.',
            'payment_gateways_image' => '',
            'payment_gateways_features' => json_encode([
                ['title_ar' => 'فيزا وماستركارد', 'title_en' => 'Visa & Mastercard'],
                ['title_ar' => 'أبل باي', 'title_en' => 'Apple Pay'],
                ['title_ar' => 'مدى', 'title_en' => 'Mada'],
                ['title_ar' => 'STC Pay', 'title_en' => 'STC Pay'],
            ]),

            // ShippingCompaniesSettings
            'shipping_companies_section_active' => '1',
            'shipping_companies_small_title_ar' => 'شركات الشحن',
            'shipping_companies_small_title_en' => 'Shipping Companies',
            'shipping_companies_main_title_ar' => 'شركاء التوصيل الموثوقون',
            'shipping_companies_main_title_en' => 'Trusted Delivery Partners',
            'shipping_companies_description_ar' => 'نتعاون مع أفضل شركات الشحن لضمان وصول طلباتك في أسرع وقت.',
            'shipping_companies_description_en' => 'We partner with the best shipping companies to ensure your orders arrive as quickly as possible.',
            'shipping_companies_image' => '',
            'shipping_companies_features' => json_encode([
                ['title_ar' => 'ساعي', 'title_en' => 'Smsa'],
                ['title_ar' => 'أرامكس', 'title_en' => 'Aramex'],
                ['title_ar' => 'دي إتش إل', 'title_en' => 'DHL'],
                ['title_ar' => 'زاجل', 'title_en' => 'Zajil'],
            ]),

            // MarketingChannelsSettings
            'marketing_channels_section_active' => '1',
            'marketing_channels_small_title_ar' => 'قنوات التسويق',
            'marketing_channels_small_title_en' => 'Marketing Channels',
            'marketing_channels_main_title_ar' => 'تواصل مع جمهورك أينما كان',
            'marketing_channels_main_title_en' => 'Reach Your Audience Anywhere',
            'marketing_channels_description_ar' => 'استفد من قنواتنا التسويقية المتنوعة للوصول إلى عملائك المستهدفين.',
            'marketing_channels_description_en' => 'Leverage our diverse marketing channels to reach your target customers.',
            'marketing_channels_items' => json_encode([
                ['title_ar' => 'البريد الإلكتروني', 'title_en' => 'Email Marketing', 'description_ar' => 'حملات بريدية احترافية', 'description_en' => 'Professional email campaigns', 'icons' => []],
                ['title_ar' => 'رسائل واتساب', 'title_en' => 'WhatsApp', 'description_ar' => 'تواصل مباشر عبر واتساب', 'description_en' => 'Direct communication via WhatsApp', 'icons' => []],
            ]),

            // TrainingSupportSettings
            'training_support_section_active' => '1',
            'training_support_small_title_ar' => 'التدريب والدعم',
            'training_support_small_title_en' => 'Training & Support',
            'training_support_main_title_ar' => 'نحن هنا لمساعدتك على النجاح',
            'training_support_main_title_en' => 'We\'re Here To Help You Succeed',
            'training_support_description_ar' => 'نقدم لك برامج تدريبية ودعم فني مستمر لضمان نجاح متجرك.',
            'training_support_description_en' => 'We provide training programs and ongoing technical support to ensure your store\'s success.',
            'training_support_items' => json_encode([
                ['title_ar' => 'دليل الاستخدام', 'title_en' => 'User Guide', 'description_ar' => 'دليل شامل لاستخدام المنصة', 'description_en' => 'Comprehensive platform guide', 'image' => ''],
                ['title_ar' => 'فيديوهات تعليمية', 'title_en' => 'Tutorial Videos', 'description_ar' => 'مكتبة فيديوهات تعليمية', 'description_en' => 'Educational video library', 'image' => ''],
            ]),

            // Faqs section titles
            'faqs_small_title_ar' => 'الأسئلة الشائعة',
            'faqs_small_title_en' => 'FAQs',
            'faqs_main_title_ar' => 'إجابات على أسئلتكم',
            'faqs_main_title_en' => 'Answers to Your Questions',
            'faqs_description_ar' => 'نجيب على أكثر الأسئلة شيوعاً حول خدماتنا ومنتجاتنا.',
            'faqs_description_en' => 'We answer the most common questions about our services and products.',

            // HaveQuestionSettings
            'have_question_section_active' => '1',
            'have_question_title_ar' => 'هل لديك سؤال؟',
            'have_question_title_en' => 'Have a Question?',
            'have_question_description_ar' => 'نحن هنا للإجابة على جميع استفساراتك. تواصل مع فريق الدعم لدينا.',
            'have_question_description_en' => 'We are here to answer all your inquiries. Contact our support team.',
            'have_question_link' => '/contact',

            // ContactUsSettings
            'contact_us_section_active' => '1',
            'contact_us_small_title_ar' => 'اتصل بنا',
            'contact_us_small_title_en' => 'Contact Us',
            'contact_us_main_title_ar' => 'نحن هنا لخدمتك',
            'contact_us_main_title_en' => 'We\'re Here to Serve You',
            'contact_us_description_ar' => 'يسعدنا تواصلك معنا. فريقنا جاهز للرد على استفساراتك.',
            'contact_us_description_en' => 'We are happy to hear from you. Our team is ready to answer your inquiries.',
            'contact_us_image' => '',
            'contact_us_email' => 'info@technostore.sa',
            'contact_us_phone' => '+966500000000',
            'contact_us_whatsapp' => '+966500000000',

            // Site info
            'site_logo' => '',
            'site_name' => 'متجر تكنو',

            // FooterSettings
            'footer_logo' => '',
            'footer_description_ar' => 'متجر تكنو هو وجهتك الأولى للتسوق التقني بأفضل الأسعار والخدمات.',
            'footer_description_en' => 'Techno Store is your first destination for tech shopping at the best prices and services.',
            'footer_facebook' => 'https://facebook.com/technostore',
            'footer_instagram' => 'https://instagram.com/technostore',
            'footer_tiktok' => '',
            'footer_youtube' => '',
            'footer_x' => '',
            'footer_linkedin' => '',
        ];

        foreach ($settings as $key => $value) {
            $richEditorKeys = [
                'intro_description_ar', 'intro_description_en',
                'about_description_ar', 'about_description_en',
                'ai_services_description_ar', 'ai_services_description_en',
                'payment_gateways_description_ar', 'payment_gateways_description_en',
                'shipping_companies_description_ar', 'shipping_companies_description_en',
                'marketing_channels_description_ar', 'marketing_channels_description_en',
                'training_support_description_ar', 'training_support_description_en',
                'have_question_description_ar', 'have_question_description_en',
                'contact_us_description_ar', 'contact_us_description_en',
                'footer_description_ar', 'footer_description_en',
            ];

            if (in_array($key, $richEditorKeys)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['string_value' => $value]
                );
            } else {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        // ── Plans with features ──

        $basic = Plan::create([
            'name' => ['ar' => 'خطة أساسية', 'en' => 'Basic Plan'],
            'title' => ['ar' => 'الخطة الأساسية', 'en' => 'Basic Plan'],
            'description' => ['ar' => 'انطلق مع متجرك الإلكتروني بأقل التكاليف', 'en' => 'Launch your online store with minimal costs'],
            'type' => 'subscription',
            'price' => 99,
            'currency' => 'SAR',
            'subscription_period' => 'monthly',
            'is_active' => true,
            'order' => 1,
        ]);

        $basic->features()->createMany([
            ['name' => ['ar' => 'منتجات غير محدودة', 'en' => 'Unlimited Products'], 'is_active' => true, 'order' => 1],
            ['name' => ['ar' => 'استضافة مجانية', 'en' => 'Free Hosting'], 'is_active' => true, 'order' => 2],
            ['name' => ['ar' => 'دعم فني', 'en' => 'Technical Support'], 'is_active' => true, 'order' => 3],
            ['name' => ['ar' => 'تقارير أساسية', 'en' => 'Basic Reports'], 'is_active' => true, 'order' => 4],
        ]);

        $pro = Plan::create([
            'name' => ['ar' => 'خطة احترافية', 'en' => 'Pro Plan'],
            'title' => ['ar' => 'الخطة الاحترافية', 'en' => 'Pro Plan'],
            'description' => ['ar' => 'لمتجر متكامل مع مميزات متقدمة', 'en' => 'For a full-featured store with advanced features'],
            'type' => 'subscription',
            'price' => 199,
            'currency' => 'SAR',
            'subscription_period' => 'monthly',
            'is_active' => true,
            'order' => 2,
        ]);

        $pro->features()->createMany([
            ['name' => ['ar' => 'جميع مميزات الخطة الأساسية', 'en' => 'All Basic Plan Features'], 'is_active' => true, 'order' => 1],
            ['name' => ['ar' => 'استخدام API', 'en' => 'API Access'], 'is_active' => true, 'order' => 2],
            ['name' => ['ar' => 'تقارير متقدمة', 'en' => 'Advanced Reports'], 'is_active' => true, 'order' => 3],
            ['name' => ['ar' => 'نطاق مخصص', 'en' => 'Custom Domain'], 'is_active' => true, 'order' => 4],
            ['name' => ['ar' => 'أولوية الدعم الفني', 'en' => 'Priority Support'], 'is_active' => true, 'order' => 5],
        ]);

        $enterprise = Plan::create([
            'name' => ['ar' => 'خطة مؤسسات', 'en' => 'Enterprise Plan'],
            'title' => ['ar' => 'خطة المؤسسات', 'en' => 'Enterprise Plan'],
            'description' => ['ar' => 'للشركات الكبيرة التي تحتاج حلول متكاملة', 'en' => 'For large companies needing comprehensive solutions'],
            'type' => 'subscription',
            'price' => 499,
            'currency' => 'SAR',
            'subscription_period' => 'monthly',
            'is_active' => true,
            'order' => 3,
        ]);

        $enterprise->features()->createMany([
            ['name' => ['ar' => 'جميع مميزات الخطة الاحترافية', 'en' => 'All Pro Plan Features'], 'is_active' => true, 'order' => 1],
            ['name' => ['ar' => 'دعم أولويات مخصصة', 'en' => 'Dedicated Priority Support'], 'is_active' => true, 'order' => 2],
            ['name' => ['ar' => 'استشارات تقنية', 'en' => 'Technical Consulting'], 'is_active' => true, 'order' => 3],
            ['name' => ['ar' => 'تدريب فريق', 'en' => 'Team Training'], 'is_active' => true, 'order' => 4],
            ['name' => ['ar' => 'SLA 99.9%', 'en' => '99.9% SLA'], 'is_active' => true, 'order' => 5],
        ]);

        // ── FAQ (general, not attached to any model) ──

        Faq::create([
            'question' => ['ar' => 'كيف يمكنني إنشاء متجر إلكتروني؟', 'en' => 'How can I create an online store?'],
            'answer' => ['ar' => 'يمكنك إنشاء متجر إلكتروني بخطوات بسيطة من خلال منصتنا. سجل حساب واختر الخطة المناسبة.', 'en' => 'You can create an online store in simple steps through our platform. Register an account and choose the right plan.'],
            'order' => 1,
            'is_active' => true,
        ]);

        Faq::create([
            'question' => ['ar' => 'ما هي طرق الدفع المتاحة؟', 'en' => 'What payment methods are available?'],
            'answer' => ['ar' => 'نقبل فيزا، ماستركارد، مدى، STC Pay، وأبل باي.', 'en' => 'We accept Visa, Mastercard, Mada, STC Pay, and Apple Pay.'],
            'order' => 2,
            'is_active' => true,
        ]);

        Faq::create([
            'question' => ['ar' => 'كم تستغرق عملية الشحن؟', 'en' => 'How long does shipping take?'],
            'answer' => ['ar' => 'يتم الشحن خلال 24 ساعة داخل المملكة العربية السعودية.', 'en' => 'Shipping takes 24 hours within Saudi Arabia.'],
            'order' => 3,
            'is_active' => true,
        ]);

        Faq::create([
            'question' => ['ar' => 'هل يمكنني إلغاء الطلب؟', 'en' => 'Can I cancel my order?'],
            'answer' => ['ar' => 'نعم، يمكنك إلغاء الطلب خلال 24 ساعة من تأكيد الطلب.', 'en' => 'Yes, you can cancel within 24 hours of order confirmation.'],
            'order' => 4,
            'is_active' => true,
        ]);

        Faq::create([
            'question' => ['ar' => 'كيف يمكنني التواصل مع الدعم الفني؟', 'en' => 'How can I contact technical support?'],
            'answer' => ['ar' => 'يمكنك التواصل معنا عبر صفحة اتصل بنا، أو عبر البريد الإلكتروني، أو الواتساب.', 'en' => 'You can contact us via the contact page, email, or WhatsApp.'],
            'order' => 5,
            'is_active' => true,
        ]);

        // ── Categories (for themes) ──

        $catElectronics = Category::create([
            'name' => ['ar' => 'إلكترونيات', 'en' => 'Electronics'],
            'slug' => 'electronics',
            'is_active' => true,
            'order' => 1,
        ]);

        $catFashion = Category::create([
            'name' => ['ar' => 'أزياء', 'en' => 'Fashion'],
            'slug' => 'fashion',
            'is_active' => true,
            'order' => 2,
        ]);

        $catFood = Category::create([
            'name' => ['ar' => 'مطاعم وطعام', 'en' => 'Food & Restaurants'],
            'slug' => 'food',
            'is_active' => true,
            'order' => 3,
        ]);

        $catServices = Category::create([
            'name' => ['ar' => 'خدمات', 'en' => 'Services'],
            'slug' => 'services',
            'is_active' => true,
            'order' => 4,
        ]);

        // ── Themes ──

        $theme1 = Theme::create([
            'name' => ['ar' => 'متجر إلكتروني حديث', 'en' => 'Modern Store'],
            'description' => ['ar' => 'تصميم عصري وجذاب مناسب لجميع أنواع المتاجر', 'en' => 'Modern and attractive design suitable for all types of stores'],
            'slug' => 'modern-store',
            'image' => '',
            'preview_url' => '#',
            'is_free' => true,
            'featured' => true,
            'is_active' => true,
            'order' => 1,
        ]);
        $theme1->categories()->attach($catElectronics->id);

        $theme2 = Theme::create([
            'name' => ['ar' => 'متجر أزياء', 'en' => 'Fashion Store'],
            'description' => ['ar' => 'تصميم أنيق يناسب متاجر الأزياء والملابس', 'en' => 'Elegant design suitable for fashion and clothing stores'],
            'slug' => 'fashion-store',
            'image' => '',
            'preview_url' => '#',
            'is_free' => false,
            'price' => 49.99,
            'featured' => false,
            'is_active' => true,
            'order' => 2,
        ]);
        $theme2->categories()->attach($catFashion->id);

        $theme3 = Theme::create([
            'name' => ['ar' => 'متجر مطعم', 'en' => 'Restaurant Store'],
            'description' => ['ar' => 'تصميم مميز للمطاعم ومحلات الطعام', 'en' => 'Distinctive design for restaurants and food shops'],
            'slug' => 'restaurant-store',
            'image' => '',
            'preview_url' => '#',
            'is_free' => true,
            'featured' => true,
            'is_active' => true,
            'order' => 3,
        ]);
        $theme3->categories()->attach($catFood->id);

        // ── Blog Categories ──

        $blogCatTech = BlogCategory::create([
            'name' => ['ar' => 'تقنية', 'en' => 'Technology'],
            'slug' => 'technology',
            'is_active' => true,
        ]);

        $blogCatGuides = BlogCategory::create([
            'name' => ['ar' => 'أدلة وشروحات', 'en' => 'Guides & Tutorials'],
            'slug' => 'guides',
            'is_active' => true,
        ]);

        $blogCatNews = BlogCategory::create([
            'name' => ['ar' => 'أخبار', 'en' => 'News'],
            'slug' => 'news',
            'is_active' => true,
        ]);

        // ── Tags ──

        $tagNew = Tag::create(['name' => ['ar' => 'جديد', 'en' => 'New'], 'slug' => 'new']);
        $tagTrending = Tag::create(['name' => ['ar' => 'رائج', 'en' => 'Trending'], 'slug' => 'trending']);
        $tagTips = Tag::create(['name' => ['ar' => 'نصائح', 'en' => 'Tips'], 'slug' => 'tips']);

        // ── Blogs ──

        $blog1 = Blog::create([
            'title' => ['ar' => 'أحدث التقنيات في عالم التجارة الإلكترونية', 'en' => 'Latest Technologies in E-Commerce'],
            'slug' => 'latest-ecommerce-technologies',
            'description' => ['ar' => 'تعرف على أحدث التقنيات التي تشكل مستقبل التجارة الإلكترونية', 'en' => 'Discover the latest technologies shaping the future of e-commerce'],
            'content' => ['ar' => '<p>محتوى المقال حول أحدث التقنيات في التجارة الإلكترونية...</p>', 'en' => '<p>Article content about latest e-commerce technologies...</p>'],
            'image' => '',
            'is_featured' => true,
            'is_active' => true,
            'published_at' => now(),
            'order' => 1,
        ]);
        $blog1->categories()->attach($blogCatTech->id);
        $blog1->tags()->attach([$tagNew->id, $tagTrending->id]);

        $blog2 = Blog::create([
            'title' => ['ar' => 'دليل شامل لإنشاء متجر إلكتروني ناجح', 'en' => 'Complete Guide to Building a Successful Online Store'],
            'slug' => 'successful-online-store-guide',
            'description' => ['ar' => 'خطوات عملية لإنشاء وإدارة متجر إلكتروني يحقق النجاح', 'en' => 'Practical steps to create and manage a successful online store'],
            'content' => ['ar' => '<p>محتوى دليل إنشاء المتجر الإلكتروني...</p>', 'en' => '<p>Guide content for building an online store...</p>'],
            'image' => '',
            'is_featured' => true,
            'is_active' => true,
            'published_at' => now(),
            'order' => 2,
        ]);
        $blog2->categories()->attach($blogCatGuides->id);
        $blog2->tags()->attach([$tagTips->id]);
    }
}
