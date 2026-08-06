<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => [
                    'ar' => 'سياسة الخصوصية',
                    'en' => 'Privacy Policy',
                ],
                'content' => [
                    'ar' => '<h2>1. مقدمة</h2><p>توضح سياسة الخصوصية هذه كيفية جمع بياناتك واستخدامها وتخزينها وحمايتها عند استخدام منصة تكنو.</p><h2>2. البيانات التي نجمعها</h2><p>قد نقوم بجمع اسمك وبريدك الإلكتروني وبيانات الدخول وتفاصيل الجلسة، وبيانات التواصل المتعلقة بالحسابات المتصلة مثل فيسبوك ماسنجر وواتساب.</p><h2>3. كيفية استخدام البيانات</h2><p>نستخدم بياناتك لتقديم خدمات المنصة وتحسينها، وتوجيه الإشعارات، وتوفير الدعم الفني، وضمان أمان النظام.</p><h2>4. مشاركة البيانات</h2><p>نحن لا نبيع بياناتك الشخصية. قد نشارك البيانات مع ميتا فقط عند الحاجة لتشغيل خدمات التراسل المتصلة، أو عند الاقتضاء القانوني.</p><h2>5. الاحتفاظ بالبيانات</h2><p>نحتفظ ببياناتك طالما كان حسابك نشطًا، وبعد ذلك للمدة اللازمة للأغراض القانونية والأمنية.</p><h2>6. الأمان</h2><p>نطبق إجراءات أمنية مناسبة بما في ذلك تشفير رموز الوصول وتقييد الوصول للوحات التحكم.</p><h2>7. التواصل معنا</h2><p>لأي استفسارات حول سياسة الخصوصية، يرجى التواصل معنا عبر البريد الإلكتروني المتاح على الموقع.</p>',
                    'en' => '<h2>1. Introduction</h2><p>This Privacy Policy explains how we collect, use, store, and protect your information when you use the Techno platform.</p><h2>2. Information we collect</h2><p>We may process your name, email address, login credentials, and session details, as well as messaging data related to connected accounts such as Facebook Messenger and WhatsApp.</p><h2>3. How we use information</h2><p>We use your information to provide and improve platform services, route notifications, deliver support, and maintain platform security.</p><h2>4. Data sharing</h2><p>We do not sell your personal data. We may share data with Meta only as required to operate connected messaging services, or when required by law.</p><h2>5. Data retention</h2><p>We retain your data while your account remains active, and afterwards for as long as reasonably necessary for legal, security, or operational purposes.</p><h2>6. Security</h2><p>We apply appropriate technical and organizational measures, including encryption of access tokens and authenticated access controls.</p><h2>7. Contact</h2><p>For any questions about this Privacy Policy, please contact us through the email address listed on the website.</p>',
                ],
                'is_active' => true,
                'show_in_footer' => true,
                'sort_order' => 1,
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'terms-and-conditions'],
            [
                'title' => [
                    'ar' => 'الشروط والأحكام',
                    'en' => 'Terms and Conditions',
                ],
                'content' => [
                    'ar' => '<h2>1. قبول الشروط</h2><p>باستخدامك منصة تكنو فإنك توافق على الالتزام بهذه الشروط والأحكام.</p><h2>2. استخدام المنصة</h2><p>يجب استخدام المنصة للأغراض المشروعة فقط، ويمنع استخدامها بأي طريقة تضر بالمنصة أو المستخدمين الآخرين.</p><h2>3. الحسابات</h2><p>أنت مسؤول عن الحفاظ على سرية بيانات دخولك وعن جميع الأنشطة التي تتم من خلال حسابك.</p><h2>4. الاشتراكات والدفع</h2><p>تخضع الاشتراكات والمدفوعات لشروط خطة الاشتراك المختارة، ويحق للمنصة تحديث الأسعار مع إشعار مسبق.</p><h2>5. الملكية الفكرية</h2><p>جميع المحتويات والعلامات التجارية الخاصة بالمنصة مملوكة لها ولا يجوز استخدامها دون إذن.</p><h2>6. إخلاء المسؤولية</h2><p>تُقدم المنصة كما هي، ولا تتحمل المنصة مسؤولية أي أضرار مباشرة أو غير مباشرة ناتجة عن استخدامها.</p><h2>7. التواصل معنا</h2><p>لأي استفسارات حول هذه الشروط، يرجى التواصل معنا عبر البريد الإلكتروني المتاح على الموقع.</p>',
                    'en' => '<h2>1. Acceptance of terms</h2><p>By using the Techno platform, you agree to comply with these Terms and Conditions.</p><h2>2. Use of the platform</h2><p>The platform must be used for lawful purposes only. You may not use it in any way that harms the platform or other users.</p><h2>3. Accounts</h2><p>You are responsible for safeguarding your login credentials and for all activity that occurs under your account.</p><h2>4. Subscriptions and payments</h2><p>Subscriptions and payments are subject to the terms of the selected subscription plan. The platform may update pricing with prior notice.</p><h2>5. Intellectual property</h2><p>All platform content and trademarks are owned by the platform and may not be used without permission.</p><h2>6. Disclaimer</h2><p>The platform is provided as-is. The platform is not liable for any direct or indirect damages arising from its use.</p><h2>7. Contact</h2><p>For any questions about these Terms, please contact us through the email address listed on the website.</p>',
                ],
                'is_active' => true,
                'show_in_footer' => true,
                'sort_order' => 2,
            ]
        );
    }
}
