<?php

return array (
    'nav' => 
    array (
        'pipeline' => 'خط المبيعات',
        'messaging' => 'صناديق المراسلة',
        'channel_connections' => 'ربط القنوات',
        'logs_diagnostics' => 'السجلات والتشخيص',
        'integrations' => 'التكاملات',
        'settings' => 'إعدادات CRM',
        'commissions' => 'العمولات',
        'reports' => 'التقارير',
    ),
    'channels' => 
    array (
        'nav' => 
        array (
            'whatsapp_logs' => 'سجلات WhatsApp',
            'messenger_logs' => 'سجلات Messenger',
            'instagram_logs' => 'سجلات Instagram',
            'meta_webhook_events' => 'أحداث Meta Webhook',
            'whatsapp_connection' => 'تشخيص WhatsApp',
            'messenger_connection' => 'تشخيص Messenger',
            'instagram_connection' => 'تشخيص Instagram',
            'instagram_integration' => 'ربط Instagram',
            'whatsapp_integration' => 'تكامل WhatsApp',
            'connect_whatsapp' => 'ربط WhatsApp',
            'connect_messenger' => 'ربط صفحات Facebook',
            'connect_instagram' => 'ربط Instagram',
            'connect_instagram_facebook_legacy' => 'قديم: Instagram عبر Facebook Login',
            'facebook_connect' => 'ربط صفحات Facebook',
            'conversation_inbox' => 'صندوق المحادثات الموحد',
            'whatsapp_inbox' => 'صندوق WhatsApp',
            'messenger_inbox' => 'صندوق Messenger',
            'instagram_inbox' => 'صندوق Instagram',
            'capture' => 'التقاط العملاء',
        ),
        'logs' => 
        array (
            'tabs' => 
            array (
                'webhooks' => 'أحداث Webhook',
                'api' => 'طلبات API',
                'api_outbound' => 'طلبات API (صادر)',
            ),
            'columns' => 
            array (
                'received_at' => 'وقت الاستلام',
                'event_type' => 'نوع الحدث',
                'direction' => 'الاتجاه',
                'summary' => 'الوصف',
                'processing' => 'المعالجة',
                'signature' => 'التوقيع',
                'client' => 'العميل',
                'attempts' => 'محاولات',
                'duration_ms' => 'المدة (ms)',
                'time' => 'الوقت',
                'operation' => 'العملية',
                'method' => 'الطريقة',
                'http' => 'HTTP',
                'error_code' => 'كود الخطأ',
                'mid' => 'MID',
                'psid' => 'PSID',
                'igsid' => 'IGSID',
                'subtype' => 'النوع الفرعي',
                'account_ref' => 'Account / Page / Phone ID',
                'sender' => 'المرسل',
                'failure_reason' => 'سبب الفشل / ملاحظة',
                'processed_at' => 'وقت المعالجة',
                'payload_shape' => 'شكل Payload',
                'event_source' => 'مصدر الحدث',
            ),
            'filters' => 
            array (
                'event_type' => 'نوع الحدث',
                'payload_shape' => 'شكل Payload',
                'event_source' => 'مصدر الحدث',
                'processing_status' => 'حالة المعالجة',
                'signature_valid' => 'توقيع صالح',
                'received_at' => 'تاريخ الاستلام',
            ),
            'actions' => 
            array (
                'view' => 'التفاصيل',
                'reprocess' => 'إعادة المعالجة',
                'view_payload' => 'عرض Payload',
                'close' => 'إغلاق',
            ),
            'modal' => 
            array (
                'api_details' => 'تفاصيل طلب API',
            ),
            'notifications' => 
            array (
                'reprocess_scheduled' => 'تمت جدولة إعادة المعالجة',
                'reprocess_queued' => 'تمت جدولة إعادة المعالجة',
            ),
            'empty' => 
            array (
                'webhooks_heading' => 'لا توجد أحداث webhook',
                'api_heading' => 'لا توجد طلبات API',
                'webhooks_desc_whatsapp' => 'عند وصول إشعارات من Meta ستظهر هنا مع وصف مبسّط.',
                'webhooks_desc_messenger' => 'عند وصول إشعارات Messenger من Meta ستظهر هنا مع وصف مبسّط.',
                'webhooks_desc_instagram' => 'عند وصول إشعارات Instagram من Meta ستظهر هنا مع وصف مبسّط.',
                'api_desc_whatsapp' => 'طلبات الإرسال والمزامنة مع Meta تُسجَّل هنا.',
                'api_desc_messenger' => 'طلبات الإرسال وجلب الملفات الشخصية مع Meta تُسجَّل هنا.',
                'api_desc_instagram' => 'طلبات الإرسال وجلب الملفات الشخصية مع Instagram Graph تُسجَّل هنا.',
            ),
            'detail' => 
            array (
                'status' => 'الحالة',
                'direction' => 'الاتجاه',
                'page' => 'الصفحة',
                'psid' => 'PSID',
                'igsid' => 'IGSID',
                'mid' => 'MID',
                'signature' => 'التوقيع',
                'valid' => 'صالح',
                'invalid' => 'غير صالح',
                'client' => 'العميل',
                'conversation' => 'المحادثة',
                'error' => 'الخطأ',
                'instagram_account' => 'حساب Instagram',
                'subtype' => 'النوع الفرعي',
                'messaging_event' => 'حدث messaging:',
                'event_payload' => 'payload الحدث:',
                'parent_payload' => 'الطلب الكامل (parent):',
            ),
        ),
        'templates' => 
        array (
            'sync' => 'مزامنة القوالب',
            'sync_heading' => 'مزامنة قوالب WhatsApp',
            'sync_desc' => 'سيتم جلب القوالب المعتمدة من Meta Business وتحديث القائمة المحلية.',
            'synced_title' => 'تمت مزامنة القوالب',
            'synced_body' => 'تم جلب :count قالبًا من Meta.',
            'sync_failed' => 'فشلت مزامنة القوالب',
        ),
        'instagram_connection' => 
        array (
            'title' => 'فحص اتصال Instagram',
            'check_failed_title' => 'فشل فحص اتصال Instagram',
            'check_failed_body' => 'الحالة: :status — :error',
            'status_unknown' => 'غير معروف',
            'healthy_title' => 'اتصال Instagram سليم',
            'healthy_body' => 'التوكن صالح — @:username (:account_id).',
            'mismatch_title' => 'تحذير: التوكن لا يطابق الحساب المُعدّ',
            'mismatch_body' => 'Instagram ID من API: :api_id — META_INSTAGRAM_ACCOUNT_ID=:configured. قد يختلف المعرف عن لوحة Embedded Setup؛ استخدم القيمة التي يرجعها /me.',
        ),
        'facebook_connect' => 
        array (
            'title' => 'ربط صفحة Facebook',
            'auth_completed_title' => 'اكتمل تفويض Facebook',
            'auth_completed_body' => 'تم تحميل الصفحات المُدارة. اختر صفحة واحدة لإكمال اختبار App Review.',
            'auth_failed_title' => 'فشل تفويض Facebook',
            'no_test_title' => 'لا يوجد اختبار تفويض',
            'no_test_body' => 'اربط صفحة Facebook أولًا لتحميل الصفحات المُدارة.',
            'invalid_selection_title' => 'اختيار صفحة غير صالح',
            'test_saved_title' => 'تم حفظ صفحة الاختبار',
            'test_saved_body' => 'يُحفظ هذا الاختيار لأغراض App Review فقط ولا يغيّر بيانات اعتماد Messenger في الإنتاج.',
            'status' => 
            array (
                'not_configured' => 'غير مُعدّ في ملف .env',
                'connected' => 'متصل',
                'token_check_failed' => 'مُعدّ لكن فشل فحص التوكن',
            ),
            'page' => 
            array (
                'production_heading' => 'اتصال Messenger الإنتاجي الحالي',
                'production_desc' => 'عرض للقراءة فقط لبيئة Messenger الحية. الإنتاج يستخدم بيانات اعتماد <code>.env</code>.',
                'current_page_id' => 'معرّف الصفحة الحالي',
                'connection_status' => 'حالة الاتصال',
                'page_name_from_token' => 'اسم الصفحة (من فحص التوكن)',
                'auth_test_heading' => 'اختبار تفويض صفحة Facebook',
                'auth_test_desc' => 'اختبار OAuth لصلاحية <code>pages_show_list</code> وApp Review فقط. لا يستبدل بيانات اعتماد الإنتاج.',
                'connect_button' => 'ربط صفحة Facebook',
                'no_test_yet' => 'لا يوجد اختبار تفويض بعد. اضغط «ربط» لتسجيل الدخول إلى Meta وتحميل الصفحات المُدارة.',
                'authorized_by' => 'تم التفويض بواسطة المستخدم #:id',
                'no_managed_pages' => 'لم تُرجِع Meta أي صفحات مُدارة لهذا التفويض.',
                'save_selected' => 'حفظ الصفحة المختارة (اختبار فقط)',
                'selected_test_page' => 'صفحة الاختبار المختارة',
                'matches_production' => 'معرّف الصفحة المختار يطابق إعداد الإنتاج (:id).',
                'not_match_production' => 'معرّف الصفحة المختار لا يطابق إعداد الإنتاج (الإنتاج: :id).',
            ),
        ),
        'capture' => 
        array (
            'filters' => 
            array (
                'channel' => 'القناة',
                'all_channels' => 'كل القنوات',
                'lead_source' => 'مصدر العميل',
            ),
            'columns' => 
            array (
                'is_new' => 'جديد',
                'client' => 'العميل',
                'source' => 'المصدر',
                'channel' => 'قناة التواصل',
                'phone_identity' => 'الهاتف / الهوية',
                'last_message' => 'آخر رسالة',
                'waiting_since' => 'منذ',
            ),
            'action_capture' => 'التقاط',
            'capture_heading' => 'تأكيد التقاط العميل',
            'capture_desc' => 'سيتم ربط العميل بك وفتح المحادثة للرد فورًا.',
            'captured_title' => 'تم التقاط العميل',
            'capture_failed_title' => 'تعذر التقاط العميل',
            'empty_heading' => 'لا يوجد عملاء بانتظار التقاط',
            'empty_desc' => 'عند وصول رسائل من قنوات غير مرتبطة بموظف ستظهر هنا.',
        ),
        'conversation_inbox' => 
        array (
            'sync_templates' => 'مزامنة قوالب واتساب',
            'sync_templates_heading' => 'مزامنة قوالب WhatsApp',
        ),
        'lead_clients' => 
        array (
            'add' => 'إضافة عميل محتمل',
            'add_heading' => 'إضافة عميل محتمل',
            'save' => 'حفظ',
            'cannot_add_title' => 'لا يمكن إضافة العميل',
            'added_title' => 'تمت الإضافة بنجاح',
            'stage_change' => 'تغيير حالة العملاء',
            'stage_change_submit' => 'تطبيق التغيير',
            'stage_change_desc' => 'اختر الحالة الجديدة للعملاء المحددين.',
            'stage_changed_title' => 'تم تحديث حالة العملاء',
        ),
        'whatsapp_integration' => 
        array (
            'title' => 'تكامل واتساب',
            'config' => 
            array (
                'heading' => 'حالة الإعداد',
                'meta_app_id' => 'Meta App ID',
                'meta_app_secret' => 'Meta App Secret',
                'oauth_redirect_uri' => 'OAuth Redirect URI',
                'embedded_signup_config_id' => 'Embedded Signup Config ID',
                'embedded_signup_flow' => 'Embedded Signup Flow',
                'setup_mode' => 'وضع إعداد واتساب الحالي',
                'callback_route' => 'Callback Route',
                'overall_readiness' => 'الجاهزية العامة',
                'configured' => 'مُعد',
                'missing' => 'ناقص',
                'ready' => 'جاهز',
                'not_ready' => 'غير جاهز',
                'missing_vars' => 'متغيرات البيئة المطلوبة الناقصة',
                'flow_standard' => 'Standard Embedded Signup',
                'flow_coexistence' => 'WhatsApp Business App Onboarding / Coexistence',
                'setup_mode_manual' => 'يدوي',
                'setup_mode_embedded' => 'Embedded Signup',
                'coexistence_warning' => 'Coexistence requires a Meta Embedded Signup configuration that explicitly supports WhatsApp Business App onboarding. The CRM setting alone does not guarantee that the Meta configuration is enabled.',
            ),
            'connection' => 
            array (
                'heading' => 'اتصال واتساب الحالي',
                'runtime_mode' => 'وضع التشغيل',
                'setup_source' => 'مصدر الإعداد',
                'source_manual' => 'يدوي (.env runtime)',
                'source_embedded' => 'Embedded Signup',
                'stored_heading' => 'حساب Embedded Signup المخزّن',
                'waba_id' => 'WABA ID',
                'phone_number_id' => 'Phone Number ID',
                'display_phone' => 'رقم الهاتف المعروض',
                'verified_name' => 'الاسم الموثّق',
                'connection_status' => 'حالة الاتصال',
                'business_portfolio_id' => 'Business Portfolio ID',
                'token_type' => 'نوع التوكن',
                'token_expiry' => 'انتهاء التوكن',
                'last_synced' => 'آخر مزامنة',
                'encrypted_token_exists' => 'توكن مشفّر مخزّن',
                'yes' => 'نعم',
                'no' => 'لا',
                'no_stored' => 'No stored Embedded Signup connection was found.',
            ),
            'actions' => 
            array (
                'heading' => 'Embedded Signup',
                'start' => 'Start Embedded Signup',
                'loading' => 'Connecting…',
                'manual_mode_warning' => 'Completing Embedded Signup stores the authorized account, but it does not automatically change WHATSAPP_SETUP_MODE. The current manual WhatsApp connection will remain the active runtime source until the environment setting is changed manually.',
            ),
            'replace_modal' => 
            array (
                'heading' => 'تأكيد استبدال حساب واتساب',
                'body' => 'An active WhatsApp account is already stored in the CRM.',
                'warning' => 'A successful flow may update the stored WhatsApp record and deactivate other WhatsApp channel account rows in the database. This does not delete or disconnect anything at Meta, but it changes the internal connection record.',
                'type_phrase' => 'Type I UNDERSTAND to continue',
                'phrase_required' => 'You must type I UNDERSTAND exactly to continue.',
                'cancel' => 'إلغاء',
                'continue' => 'Continue to Meta',
            ),
            'review_helper' => 
            array (
                'heading' => 'App Review Helper',
                'intro' => 'This page allows an authorized FlyAram administrator to start Meta-hosted WhatsApp onboarding, authorize access to FlyAram business assets, and verify the WhatsApp Business Account and phone number stored by the CRM.',
                'no_secrets' => 'No access tokens, app secrets, or authorization headers are displayed on this page.',
                'hosted_test_link' => 'Open Meta-hosted Embedded Signup test page',
            ),
            'notifications' => 
            array (
                'success_title' => 'WhatsApp account connected successfully',
                'success_body' => 'The account was stored securely. The current runtime mode has not been changed automatically.',
                'success_body_v4' => 'The authorized WhatsApp Business Account and phone number were stored securely. The current runtime mode remains Manual.',
                'cancelled_title' => 'WhatsApp onboarding was cancelled',
                'cancelled_body' => 'No runtime configuration was changed.',
                'confirm_replace_title' => 'Active WhatsApp account already exists',
                'confirm_replace_body' => 'Return to the integration page and explicitly confirm before starting a replacement flow.',
                'failed_title' => 'WhatsApp onboarding could not be completed',
                'failed_body' => 'Review the application logs for the sanitized technical reason.',
                'invalid_session_title' => 'Embedded Signup session invalid',
                'invalid_session_body' => 'The Embedded Signup session is invalid or has expired. Please start the process again.',
                'asset_mismatch_title' => 'WhatsApp account validation failed',
                'asset_mismatch_body' => 'The WhatsApp account information returned by Meta could not be validated.',
                'config_incomplete_title' => 'Meta configuration incomplete',
                'config_incomplete_body' => 'Complete the required environment variables before starting Embedded Signup.',
            ),
        ),
        'connect_whatsapp' => 
        array (
            'title' => 'ربط واتساب',
            'intro' => 'اختر مسار ربط مستقل. كل مسار يستخدم Config ID الخاص به دون التحويل تلقائيًا إلى مسار آخر.',
            'manual_title' => 'ربط يدوي عبر API',
            'manual_body' => 'للإدارة والدعم والهجرة. أدخل بيانات Cloud API وتحقق عبر Graph.',
            'manual_point_1' => 'التحقق من ملكية WABA وPhone Number ID',
            'manual_point_2' => 'حفظ التوكن مشفرًا داخل قاعدة بيانات المستأجر فقط',
            'manual_point_3' => 'مزامنة السجل المركزي دون توكن',
            'manual_cta' => 'ربط يدوي',
            'manual_success' => 'تم ربط واتساب يدويًا',
            'manual_failed' => 'فشل الربط اليدوي',
            'recommended' => 'موصى به',
            'api_only_title' => 'API Only عبر Embedded Signup',
            'api_only_body' => 'ربط رقم Cloud API عبر Meta Embedded Signup بدون Coexistence.',
            'api_only_point_1' => 'يستخدم WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID',
            'api_only_point_2' => 'لا يرسل whatsapp_business_app_onboarding',
            'api_only_point_3' => 'يدعم اختيار الرقم عند التعدد دون تخمين',
            'api_only_cta' => 'بدء API Only',
            'api_only_config_missing_title' => 'Config ID لمسار API Only غير متوفر',
            'api_only_config_missing_body' => 'اضبط WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID (وMeta App ID/Secret) لتفعيل هذه البطاقة.',
            'coexistence_title' => 'WhatsApp Business App + Cloud API',
            'coexistence_body' => 'مسار Coexistence يبقي التطبيق يعمل على نفس الرقم مع تفعيل Cloud API.',
            'coexistence_point_1' => 'يستخدم WHATSAPP_EMBEDDED_SIGNUP_COEXISTENCE_CONFIG_ID',
            'coexistence_point_2' => 'يرسل featureType=whatsapp_business_app_onboarding',
            'coexistence_point_3' => 'Disconnect الآمن لا يحذف أصول Meta',
            'coexistence_cta' => 'بدء Coexistence',
            'coexistence_unavailable_cta' => 'Coexistence غير متاح',
            'coexistence_config_missing_title' => 'Config ID لمسار Coexistence غير متوفر',
            'coexistence_config_missing_body' => 'اضبط WHATSAPP_EMBEDDED_SIGNUP_COEXISTENCE_CONFIG_ID لتفعيل هذه البطاقة. لا يتم استخدام Config الخاص بـ API Only كبديل.',
            'config_required' => 'يتطلب إعدادًا',
            'accounts_heading' => 'حسابات واتساب المرتبطة',
            'no_accounts' => 'لا توجد حسابات واتساب مرتبطة بعد.',
            'sessions_heading' => 'جلسات الربط',
            'no_sessions' => 'لا توجد جلسات ربط حديثة.',
            'disconnected' => 'تم فصل واتساب من المنصة',
            'test_ok' => 'نجح اختبار الاتصال',
            'test_failed' => 'فشل اختبار الاتصال',
            'sync_ok' => 'تمت مزامنة البيانات',
            'sync_failed' => 'فشلت مزامنة البيانات',
            'es_success' => 'اكتمل Embedded Signup',
            'es_failed' => 'فشل Embedded Signup',
            'reconnect_manual_hint' => 'حدّث بيانات الاعتماد في بطاقة الربط اليدوي لإعادة ربط حساب يدوي.',
            'set_default' => 'تعيين كرقم افتراضي',
            'default_set' => 'تم تحديث رقم واتساب الافتراضي',
            'add_another' => 'إضافة رقم آخر',
        ),
        'connect_messenger' => 
        array (
            'title' => 'ربط ماسنجر',
            'intro' => 'اربط صفحة أو أكثر من صفحات فيسبوك. OAuth للتجار؛ الربط اليدوي متاح دائمًا للإدارة والدعم.',
            'recommended' => 'موصى به للتجار',
            'oauth_title' => 'الربط عبر فيسبوك',
            'oauth_body' => 'تسجيل دخول فيسبوك، اختيار الصفحات صراحةً، وحفظ Page Access Token داخل قاعدة بيانات المستأجر فقط.',
            'oauth_cta' => 'الربط عبر فيسبوك',
            'oauth_config_missing_title' => 'Facebook Login غير مُعدّ',
            'oauth_config_missing_body' => 'اضبط META App ID/Secret وMETA_MESSENGER_OAUTH_REDIRECT_URI لتفعيل هذه البطاقة.',
            'oauth_failed' => 'فشل بدء Facebook Login',
            'manual_title' => 'ربط يدوي للصفحة',
            'manual_body' => 'للإدارة والدعم والهجرة. أدخل Page ID وPage Access Token وتحقق عبر Graph.',
            'manual_cta' => 'ربط يدوي',
            'manual_success' => 'تم ربط صفحة ماسنجر يدويًا',
            'manual_failed' => 'فشل الربط اليدوي',
            'set_default' => 'تعيين كصفحة افتراضية',
            'picker_title' => 'اختر صفحات فيسبوك',
            'picker_body' => 'تُربط الصفحات المختارة فقط. تُنظَّف التوكنات المؤقتة بعد الإتمام.',
            'picker_empty' => 'لا توجد صفحات في هذه الجلسة. أعد بدء Facebook Login.',
            'picker_cta' => 'ربط الصفحات المختارة',
            'pages_connected' => 'تم ربط صفحات ماسنجر',
            'pages_failed' => 'فشل ربط الصفحات',
            'connected_title' => 'صفحات فيسبوك المرتبطة',
            'connected_empty' => 'لا توجد صفحات ماسنجر مرتبطة بعد.',
            'default_badge' => 'افتراضي',
            'default_set' => 'تم تحديث الصفحة الافتراضية',
            'disconnected' => 'تم فصل صفحة ماسنجر من المنصة',
            'reconnect_manual_hint' => 'حدّث Page Access Token في بطاقة الربط اليدوي لإعادة الربط.',
            'test_ok' => 'نجح اختبار الاتصال',
            'test_failed' => 'فشل اختبار الاتصال',
            'sync_ok' => 'تمت مزامنة البيانات',
            'sync_failed' => 'فشلت مزامنة البيانات',
            'resubscribe_ok' => 'تم تحديث اشتراك Webhook',
            'resubscribe_failed' => 'فشل إعادة الاشتراك في Webhook',
            'action_default' => 'تعيين افتراضي',
            'action_test' => 'اختبار',
            'action_sync' => 'مزامنة',
            'action_resubscribe' => 'إعادة اشتراك',
            'action_reconnect' => 'إعادة ربط',
            'action_disconnect' => 'فصل',
        ),
        'connect_instagram' => 
        array (
            'title' => 'ربط إنستغرام (قديم — Facebook Login)',
            'legacy_title' => 'قديم: Instagram عبر Facebook Login',
            'intro' => 'مسار قديم للرجوع فقط. المسار الرسمي هو ربط Instagram عبر Instagram Login.',
            'recommended' => 'قديم / رجوع فقط',
            'oauth_title' => 'الربط عبر فيسبوك (قديم)',
            'oauth_body' => 'تسجيل دخول فيسبوك، اختيار حسابات إنستغرام صراحةً، وحفظ Page Access Token داخل قاعدة بيانات المستأجر فقط.',
            'oauth_cta' => 'الربط عبر فيسبوك (قديم)',
            'oauth_config_missing_title' => 'Facebook Login غير مُعدّ',
            'oauth_config_missing_body' => 'اضبط META App ID/Secret وMETA_INSTAGRAM_FACEBOOK_LOGIN_REDIRECT_URI لتفعيل هذه البطاقة.',
            'oauth_failed' => 'فشل بدء Facebook Login',
            'manual_title' => 'ربط يدوي لإنستغرام',
            'manual_body' => 'للإدارة والدعم والهجرة. أدخل Instagram Account ID وPage Access Token (Page ID اختياري) وتحقق عبر Graph.',
            'manual_cta' => 'ربط يدوي',
            'manual_success' => 'تم ربط حساب إنستغرام يدويًا',
            'manual_failed' => 'فشل الربط اليدوي',
            'set_default' => 'تعيين كحساب افتراضي',
            'picker_title' => 'اختر حسابات إنستغرام',
            'picker_body' => 'تُربط الحسابات المختارة فقط. تُنظَّف التوكنات المؤقتة بعد الإتمام.',
            'picker_empty' => 'لا توجد حسابات إنستغرام في هذه الجلسة. أعد بدء Facebook Login.',
            'picker_cta' => 'ربط الحسابات المختارة',
            'accounts_connected' => 'تم ربط حسابات إنستغرام',
            'accounts_failed' => 'فشل ربط الحسابات',
            'connected_title' => 'حسابات إنستغرام المرتبطة',
            'connected_empty' => 'لا توجد حسابات إنستغرام مرتبطة بعد.',
            'default_badge' => 'افتراضي',
            'default_set' => 'تم تحديث الحساب الافتراضي',
            'disconnected' => 'تم فصل حساب إنستغرام من المنصة',
            'reconnect_manual_hint' => 'حدّث Page Access Token في بطاقة الربط اليدوي لإعادة الربط.',
            'test_ok' => 'نجح اختبار الاتصال',
            'test_failed' => 'فشل اختبار الاتصال',
            'sync_ok' => 'تمت مزامنة البيانات',
            'sync_failed' => 'فشلت مزامنة البيانات',
            'resubscribe_ok' => 'تم تحديث اشتراك Webhook',
            'resubscribe_failed' => 'فشل إعادة الاشتراك في Webhook',
            'action_default' => 'تعيين افتراضي',
            'action_test' => 'اختبار',
            'action_sync' => 'مزامنة',
            'action_resubscribe' => 'إعادة اشتراك',
            'action_reconnect' => 'إعادة ربط',
            'action_disconnect' => 'فصل',
        ),
        'instagram_integration' => 
        array (
            'title' => 'ربط Instagram',
            'review_helper' => 
            array (
                'heading' => 'مساعد تصوير App Review',
                'intro' => 'سجّل هذه الخطوات بالإنجليزية لإثبات login flow كامل ورسائل end-to-end.',
                'step_connect' => 'اضغط Connect Instagram وسجّل الدخول بحساب Instagram Business.',
                'step_grant' => 'امنح instagram_business_basic و instagram_business_manage_messages (لا نطلب pages_read_engagement).',
                'step_verify' => 'ارجع لهذه الصفحة وتأكد من username و account ID.',
                'step_token_check' => 'شغّل connection check (GET /me عبر token على السيرفر).',
                'step_send_test' => 'أرسل test message من CRM إلى IGSID للمختبر.',
                'step_native_client' => 'أظهر الرسالة في تطبيق Instagram الأصلي.',
                'step_webhooks' => 'افتح Meta Webhook Events وأظهر الأحداث المستلمة.',
            ),
            'connection' => 
            array (
                'heading' => 'Connection status',
                'desc' => 'الربط عبر Instagram Login. يُحفظ Instagram User Access Token مشفّرًا ولا يُعرض. الرسائل عبر graph.instagram.com.',
            ),
            'managed_account' => 
            array (
                'heading' => 'Managed Instagram business account',
                'desc' => 'Only the managed business account @fly_aram should be connected to this CRM. Personal Meta tester accounts are for sending test DMs in the native Instagram app only.',
                'managed_username' => 'Managed account',
                'professional_id' => 'Professional account ID (webhooks & messaging API)',
                'app_scoped_id' => 'App-scoped OAuth ID (internal only — not used for webhooks)',
                'tester_heading' => 'Tester accounts (do not connect to CRM)',
                'tester_points' => 
                array (
                    0 => 'Personal tester accounts may appear in Meta Dashboard → Generate access tokens — ignore them for CRM integration.',
                    1 => 'Do not generate tokens or enable Webhook Subscription for personal tester accounts.',
                    2 => 'Use tester accounts only to send/receive test DMs to @fly_aram in the native Instagram client.',
                    3 => 'Never use a tester account ID as sender ID, recipient ID, or CRM connection ID.',
                ),
                'webhook_id_note' => 'Webhook entry.id and recipient.id use the professional account ID (often starting with 178414…). This must match the ID stored after Connect Instagram.',
                'reconnect_mismatch' => 'If the account ID here does not match Meta Dashboard for @fly_aram, click Reconnect Instagram to refresh the professional account ID.',
            ),
            'diagnostics' => 
            array (
                'heading' => 'تشخيص',
                'desc' => 'تحقق من التوكن النشط أو أرسل DM تجريبي لـ App Review.',
            ),
            'fields' => 
            array (
                'status' => 'الحالة',
                'token_source' => 'مصدر الاتصال',
                'username' => 'اسم مستخدم Instagram',
                'account_id' => 'Instagram account ID (/me user_id)',
                'account_type' => 'نوع الحساب',
                'token_expiry' => 'انتهاء التوكن',
                'last_checked' => 'آخر فحص توken',
                'last_webhook' => 'آخر webhook',
                'granted_scopes' => 'الصلاحيات الممنوحة / المطلوبة',
                'test_recipient' => 'IGSID المختبر (المستلم)',
            ),
            'status' => 
            array (
                'connected' => 'متصل عبر OAuth',
                'not_connected' => 'غير متصل — fallback من ENV إن وُجد',
            ),
            'token_source' => 
            array (
                'db' => 'OAuth Database Connection',
                'env' => 'Legacy ENV fallback',
            ),
            'notes' => 
            array (
                'env_fallback' => 'This connection is using the legacy ENV fallback. For App Review and production usage, connect Instagram through the Connect Instagram button.',
            ),
            'actions' => 
            array (
                'connect' => 'Connect Instagram',
                'reconnect' => 'Reconnect Instagram',
                'disconnect' => 'Disconnect',
                'disconnect_confirm' => 'فصل OAuth؟ السجلات تبقى. سيُستخدم ENV إن وُجد.',
                'run_check' => 'Run connection check',
                'send_test' => 'Send test message',
                'webhook_diagnostics' => 'Open webhook diagnostics',
            ),
            'notifications' => 
            array (
                'connect_success_title' => 'تم ربط Instagram',
                'connect_success_body' => 'تم تخزين OAuth token بأمان. شغّل connection check.',
                'connect_failed_title' => 'فشل ربط Instagram',
                'disconnect_success_title' => 'تم فصل Instagram',
                'check_success_title' => 'نجح connection check',
                'check_failed_title' => 'فشل connection check',
                'test_missing_recipient' => 'أدخل IGSID للمختبر.',
                'test_sent_title' => 'تم إرسال رسالة تجريبية',
                'test_sent_body' => 'تحقق من Instagram client للمختبر.',
                'test_failed_title' => 'فشل إرسال الرسالة التجريبية',
            ),
        ),
        'instagram_connection_page' => 
        array (
            'title' => 'Instagram Diagnostics',
            'subtitle' => 'Legacy connection verification and migration diagnostics. For App Review, use Instagram Integration.',
            'primary_path_heading' => 'Primary connection path: Instagram Integration',
            'primary_path_body' => 'Connect, reconnect, and demonstrate Instagram for Meta App Review on the Instagram Integration page. This diagnostics page is not the primary connection flow.',
            'primary_path_link' => 'Open Instagram Integration',
            'legacy_notice_heading' => 'Legacy diagnostics only',
            'legacy_notice_body' => 'This page helps verify the active server-side token and troubleshoot migration from legacy ENV configuration.',
            'legacy_notice_points' => 
            array (
                0 => 'The CRM uses the OAuth database connection first when one exists in crm_instagram_connections.',
                1 => 'Legacy ENV fallback (META_INSTAGRAM_ACCESS_TOKEN and META_INSTAGRAM_ACCOUNT_ID) is supported only for diagnostics or migration — not for App Review.',
                2 => 'For App Review screencasts, use Connect Instagram on the Instagram Integration page.',
                3 => 'Access tokens are never displayed in the UI or written to application logs.',
            ),
            'connection_source' => 'Connection source',
            'token_source' => 
            array (
                'db' => 'OAuth Database Connection',
                'env' => 'Legacy ENV fallback',
                'none' => 'Not configured',
            ),
            'legacy_env_warning' => 'This connection is using the legacy ENV fallback. For App Review and production usage, connect Instagram through the Connect Instagram button.',
            'id_types_heading' => 'Instagram ID types',
            'id_types_body' => 'Meta returns two IDs for Instagram Login. The CRM stores the professional account ID (user_id) — the same ID used in webhook entry.id and POST /{id}/messages. The app-scoped id from /me is not used for webhooks.',
            'managed_account_note' => 'Managed business account only: @fly_aram. Do not generate tokens or enable webhooks for personal tester accounts listed in Meta Dashboard.',
            'oauth_db_status' => 'OAuth database connection',
            'oauth_connected_yes' => 'Active OAuth connection stored',
            'oauth_connected_no' => 'No OAuth connection — ENV fallback or not configured',
            'env_fallback_configured' => 'Legacy ENV fallback configured',
            'env_fallback_yes' => 'Yes (diagnostics / migration only)',
            'env_fallback_no' => 'No',
            'configured_assets_heading' => 'Active connection summary',
            'configured_assets_desc' => 'Read-only summary of the token source and account identifiers used by the CRM runtime.',
            'verification_heading' => 'Connection check (diagnostics)',
            'verification_results_heading' => 'Latest check result',
            'instagram_business_account_id' => 'Instagram account ID (/me user_id)',
            'username' => 'Username',
            'account_type' => 'Account Type',
            'account_type_pending' => 'Run connection check to retrieve from Instagram API',
            'app_id' => 'App ID',
            'integration_status' => 'Integration status',
            'config_match' => 'Account ID match',
            'not_yet_verified' => 'Not verified yet',
            'verification_failed' => 'Verification failed',
            'last_check_time' => 'Last check time',
            'last_check_pending' => 'No check run yet',
            'display_name' => 'Display name',
            'profile_picture' => 'Profile picture',
            'current_config' => 'Current configuration',
            'status' => 'Status',
            'enabled' => 'Enabled',
            'disabled' => 'Disabled',
            'account_id_label' => 'Account ID (account IGSID)',
            'token_not_shown' => 'The access token is never displayed on this page for security reasons.',
            'token_check' => 'Connection check',
            'token_check_desc' => 'Click <strong>Run connection check</strong> to call <code class="text-xs">GET /me</code> on graph.instagram.com using the active server token. The CRM uses the <strong>OAuth database connection first</strong>; legacy ENV variables are used only when no OAuth connection exists (diagnostics / migration).',
            'run_check' => 'Run connection check',
            'connection_status' => 'حالة الاتصال',
            'success' => 'ناجح',
            'failed' => 'فشل',
            'match_config' => 'مطابقة الإعداد',
            'yes' => 'نعم',
            'no' => 'لا',
            'profile_picture_na' => 'غير متوفرة',
            'stored_id_note' => 'المعرف في البيئة: :id — قد يختلف عن لوحة Embedded Setup؛ اعتمد القيمة المُعادة من /me.',
            'error' => 'الخطأ',
            'last_check' => 'آخر فحص',
        ),
        'inbox' => 
        array (
            'search' => 
            array (
                'whatsapp' => 'بحث بالاسم أو رقم الهاتف',
                'messenger' => 'بحث بالاسم أو PSID',
                'instagram' => 'بحث بالاسم أو @username أو IGSID',
            ),
            'filters' => 
            array (
                'unassigned' => 'غير مسندة',
                'mine' => 'محادثاتي',
                'mine_alt' => 'مسندة إلي',
                'unread' => 'غير مقروءة',
                'closed' => 'مغلقة',
                'open' => 'مفتوحة',
            ),
            'no_messages' => 'لا توجد رسائل بعد',
            'empty_list' => 
            array (
                'whatsapp' => 'لا توجد محادثات في هذا التصنيف.',
                'messenger' => 'لا توجد محادثات Messenger في هذا التصنيف.',
                'instagram' => 'لا توجد محادثات Instagram.',
            ),
            'assignee' => 'المسؤول:',
            'unassigned_value' => 'غير مسند',
            'claim' => 'استلام المحادثة',
            'claim_short' => 'استلام',
            'close' => 'إغلاق',
            'reopen' => 'إعادة فتح',
            'send' => 'إرسال',
            'window_expired' => 'انتهت نافذة الـ24 ساعة. لا يمكن إرسال رسالة نصية حرة — اختر قالبًا معتمدًا أدناه.',
            'template_select_placeholder' => '— إرسال قالب WhatsApp معتمد —',
            'no_templates_notice' => 'لا توجد قوالب معتمدة محلياً. استخدم زر «مزامنة قوالب واتساب» أعلى الصفحة لجلبها من Meta.',
            'composer' => 
            array (
                'whatsapp' => 'اكتب رسالتك...',
                'whatsapp_disabled' => 'الرسائل الحرة معطّلة خارج نافذة 24 ساعة',
                'messenger' => 'اكتب رسالة Messenger...',
                'instagram' => 'اكتب رسالة Instagram...',
            ),
            'placeholder' => 
            array (
                'select_title' => 'اختر محادثة للبدء',
                'select_desc' => 'حدّد محادثة من القائمة الجانبية لعرض الرسائل والرد.',
                'messenger' => 'اختر محادثة Messenger لعرض الرسائل.',
                'instagram' => 'اختر محادثة Instagram.',
            ),
            'external_send' => 'أرسلت من Business Suite',
            'sidebar' => 
            array (
                'client' => 'العميل',
                'stage' => 'المرحلة',
                'lead_source' => 'مصدر العميل',
                'psid_admin' => 'PSID (إداري)',
                'last_activity' => 'آخر نشاط',
                'messenger_profile_status' => 'حالة ملف Messenger',
                'profile_status' => 'حالة الملف',
                'phone' => 'الهاتف',
                'synced_ago' => 'تمت المزامنة :time',
                'sync_failed' => 'تعذّرت المزامنة — يُعرض اسم بديل',
                'not_synced' => 'لم تتم المزامنة بعد',
                'refresh_messenger_profile' => 'تحديث ملف Messenger',
                'check_messenger_connection' => 'فحص اتصال Messenger',
                'refresh_profile' => 'تحديث الملف',
                'phone_note' => 'رقم الهاتف وواتساب غير متاحين تلقائيًا من Messenger.',
                'suggested_phone' => 'تم اكتشاف رقم محتمل: :phone',
                'confirm_phone' => 'تأكيد وحفظ الرقم',
                'saved_phone' => 'رقم محفوظ: :phone',
                'request_phone' => 'طلب رقم الهاتف من العميل',
                'open_client' => 'فتح ملف العميل',
                'details_here' => 'تفاصيل العميل تظهر هنا.',
            ),
            'crm_context' => 
            array (
                'title' => 'سياق CRM',
                'client' => 'العميل',
                'provisional' => 'مؤقت',
                'reply_from' => 'الرد من',
                'assigned' => 'المسند إليه',
                'branch' => 'الفرع',
                'opportunities' => 'الفرص المفتوحة',
                'identity' => 'الهوية',
                'phone' => 'الهاتف',
                'username' => 'اسم المستخدم',
                'none' => '—',
                'assign_to_me' => 'إسناد لي',
                'unassign' => 'إلغاء الإسناد',
                'create_opportunity' => 'إنشاء فرصة',
                'link_customer' => 'ربط عميل',
                'merge_provisional' => 'دمج عميل مؤقت',
                'client_id_placeholder' => 'معرّف العميل',
                'target_client_id_placeholder' => 'معرّف العميل الهدف',
                'opportunity_title_placeholder' => 'عنوان الفرصة (اختياري)',
                'submit_link' => 'ربط',
                'submit_merge' => 'دمج',
                'submit_opportunity' => 'إنشاء',
                'success' => 
                array (
                    'linked' => 'تم ربط العميل بالمحادثة',
                    'merged' => 'تم دمج العميل المؤقت',
                    'assigned_me' => 'تم إسناد المحادثة إليك',
                    'assigned' => 'تم إسناد المحادثة',
                    'unassigned' => 'تم إلغاء إسناد المحادثة',
                    'opportunity_created' => 'تم إنشاء فرصة من المحادثة',
                ),
                'errors' => 
                array (
                    'unauthorized' => 'غير مسموح لك بتنفيذ هذا الإجراء',
                    'client_not_found' => 'العميل غير موجود',
                    'user_not_found' => 'الموظف غير موجود',
                    'identity_missing' => 'لا توجد هوية مرتبطة بهذه المحادثة',
                    'not_provisional' => 'العميل الحالي ليس مؤقتًا',
                    'subscription_blocked' => 'الاشتراك أو ميزة القناة غير نشطة',
                    'no_conversation' => 'لم يتم اختيار محادثة',
                ),
            ),
        ),
    ),
    'actions' => 
    array (
        'view_client' => 'عرض العميل',
        'change_stage' => 'تغيير الحالة',
        'updated' => 'تم التعديل',
        'close' => 'إغلاق',
        'edit_client' => 'تعديل العميل',
        'save' => 'حفظ',
        'manage_opportunities' => 'إدارة الفرص',
        'open_latest_opportunity' => 'فتح آخر فرصة',
        'create_opportunity' => 'إنشاء فرصة',
        'view_open_opportunities' => 'عرض الفرص المفتوحة',
        'view_opportunity' => 'عرض الفرصة',
        'create_follow_up' => 'إنشاء متابعة',
    ),
    'sections' => 
    array (
        'details' => 'تفاصيل',
        'client_details' => 'بيانات العميل',
        'opportunities' => 'الفرص',
        'follow_ups' => 'المتابعات',
        'relations' => 'الارتباطات',
        'follow_up_content' => 'محتوى المتابعة',
    ),
    'table_groups' => 
    array (
        'client' => 'العميل',
        'company' => 'الشركة',
        'contact' => 'التواصل',
        'assignment' => 'الإسناد',
        'amounts' => 'المبالغ',
        'commercial' => 'الشروط التجارية',
        'scheduling' => 'الجدولة',
        'notes' => 'الملاحظات',
        'dates' => 'التواريخ',
    ),
    'tabs' => 
    array (
        'all' => 'الكل',
        'upcoming' => 'قادمة',
        'overdue' => 'متأخرة',
        'completed' => 'مكتملة',
        'mine' => 'متابعاتي',
    ),
    'filters' => 
    array (
        'my_follow_ups' => 'متابعاتي',
        'upcoming_week' => 'هذا الأسبوع',
    ),
    'resources' => 
    array (
        'opportunity' => 
        array (
            'navigation' => 'الفرص',
            'model' => 'فرصة',
            'plural' => 'الفرص',
        ),
        'campaign' => 
        array (
            'navigation' => 'الحملات',
            'model' => 'حملة',
            'plural' => 'الحملات',
        ),
        'opportunity_stage' => 
        array (
            'navigation' => 'مراحل الفرص',
            'model' => 'مرحلة',
            'plural' => 'مراحل الفرص',
        ),
        'follow_up_status' => 
        array (
            'navigation' => 'حالات المتابعة',
            'model' => 'حالة متابعة',
            'plural' => 'حالات المتابعة',
        ),
        'follow_up_type' => 
        array (
            'navigation' => 'أنواع المتابعة',
            'model' => 'نوع متابعة',
            'plural' => 'أنواع المتابعة',
        ),
        'follow_up' => 
        array (
            'navigation' => 'المتابعات',
            'model' => 'متابعة',
            'plural' => 'المتابعات',
        ),
        'lead_clients' => 
        array (
            'navigation' => 'العملاء المحتملون',
        ),
    ),
    'fields' => 
    array (
        'basic_info' => 'بيانات أساسية',
        'name' => 'الاسم',
        'company_name' => 'الشركة',
        'gondc_name' => 'GONDC',
        'email' => 'Email',
        'phone' => 'الهاتف',
        'tax_number' => 'الرقم الضريبي',
        'commercial_register' => 'السجل التجاري',
        'address' => 'العنوان',
        'stage' => 'المرحلة',
        'sales_rep' => 'مندوب المبيعات',
        'lead_source' => 'مصدر العميل',
        'first_followed_by' => 'أول متابعة',
        'is_provisional' => 'مؤقت',
        'client' => 'العميل',
        'campaign' => 'الحملة',
        'title' => 'العنوان',
        'amount' => 'المبلغ',
        'agreed_amount' => 'المبلغ المتفق عليه',
        'description' => 'الوصف',
        'is_closed' => 'مغلقة',
        'closed_at' => 'تاريخ الإغلاق',
        'assigned_to' => 'مسند إلى',
        'first_assigned_to' => 'أول مسند',
        'created_by' => 'أنشئ بواسطة',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'meta' => 'بيانات إضافية',
        'note' => 'ملاحظة',
        'latest_note' => 'آخر ملاحظة',
        'is_private' => 'خاصة',
        'private' => 'خاصة',
        'notes' => 'ملاحظات',
        'from_stage' => 'من مرحلة',
        'to_stage' => 'إلى مرحلة',
        'changed_by' => 'غيّر بواسطة',
        'from_user' => 'من مستخدم',
        'to_user' => 'إلى مستخدم',
        'follow_up_type' => 'نوع المتابعة',
        'follow_up_status' => 'حالة المتابعة',
        'target_stage' => 'المرحلة المستهدفة',
        'next_scheduled_at' => 'موعد المتابعة القادمة',
        'next_assigned_to' => 'مسؤول المتابعة القادمة',
        'next_follow_up_type' => 'نوع المتابعة القادمة',
        'parent_follow_up' => 'معاد جدولتها من',
        'scheduled_at' => 'موعد الجدولة',
        'completed_at' => 'تاريخ الإكمال',
        'scheduling_state' => 'حالة الجدولة',
        'offer_text' => 'نص العرض',
        'customer_reply' => 'رد العميل',
        'internal_notes' => 'ملاحظات داخلية',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'status' => 'الحالة',
        'budget' => 'الميزانية',
        'is_final' => 'مرحلة نهائية',
        'sort_order' => 'ترتيب',
        'action' => 'الإجراء',
        'open_opportunities_count' => 'الفرص النشطة',
        'won_opportunities_count' => 'الفرص الناجحة',
        'won_agreed_amount_total' => 'إجمالي السعر المتفق عليه (الناجحة)',
        'latest_opportunity' => 'آخر فرصة',
        'latest_opportunity_stage' => 'مرحلة آخر فرصة',
        'last_completed_follow_up' => 'آخر متابعة مكتملة',
        'next_scheduled_follow_up' => 'المتابعة القادمة',
        'opportunity' => 'الفرصة',
        'child_follow_ups' => 'متابعات مشتقة',
        'branch' => 'الفرع',
        'source' => 'المصدر',
        'account_tree' => 'الحساب (شجرة)',
        'accounts_center' => 'مركز الحسابات',
        'sale' => 'بيع',
        'sales_invoice' => 'فاتورة بيع',
    ),
    'hints' => 
    array (
        'next_scheduled_at' => 'سيتم إغلاق المتابعة الحالية وإنشاء متابعة جديدة في هذا الموعد.',
        'next_assigned_to' => 'الموظف الذي سيتابع المرة القادمة (افتراضياً أنت).',
    ),
    'notes' => 
    array (
        'plural' => 'الملاحظات',
        'add' => 'أضف ملاحظة',
        'view_all' => 'عرض كل الملاحظات',
        'modal_heading' => 'ملاحظات المتابعة',
        'created' => 'تم إنشاء الملاحظة',
        'empty' => 'لا توجد ملاحظات حتى الآن.',
        'private' => 'خاصة',
    ),
    'stage_logs' => 
    array (
        'plural' => 'سجل المراحل',
    ),
    'assignment_logs' => 
    array (
        'plural' => 'سجل الإسناد',
    ),
    'follow_ups' => 
    array (
        'plural' => 'المتابعات',
    ),
    'client_stages' => 
    array (
        'lead' => 'عميل محتمل',
        'customer' => 'عميل فعلي',
        'advanced' => 'عميل متقدم',
        'vip' => 'VIP',
    ),
    'stage_actions' => 
    array (
        'none' => 'لا شيء',
        'open' => 'فتح الفرصة',
        'success_close' => 'نجاح - إغلاق',
        'failed_close' => 'فشل - إغلاق',
        'reopen' => 'إعادة فتح',
    ),
    'follow_up_actions' => 
    array (
        'none' => 'لا شيء',
        'success_close' => 'نجاح - إغلاق',
        'failed_close' => 'فشل - إغلاق',
        'change_stage' => 'تغيير المرحلة',
        'schedule_next' => 'جدولة متابعة',
    ),
    'scheduling' => 
    array (
        'scheduled' => 'مجدولة',
        'overdue' => 'متأخرة',
        'completed' => 'مكتملة',
    ),
    'campaign_status_options' => 
    array (
        'draft' => 'مسودة',
        'active' => 'نشطة',
        'paused' => 'متوقفة',
        'completed' => 'مكتملة',
    ),
    'callouts' => 
    array (
        'won_title' => 'فرصة رابحة',
        'lost_title' => 'فرصة خاسرة',
        'reopened_title' => 'تم إعادة فتح الفرصة',
        'closed_title' => 'فرصة مغلقة',
        'open_title' => 'فرصة مفتوحة',
        'closed_at' => 'أُغلقت في :date',
    ),
    'messages' => 
    array (
        'invalid_stage' => 'المرحلة المحددة غير صالحة.',
        'target_stage_required' => 'المرحلة المستهدفة مطلوبة لهذه الحالة.',
        'next_scheduled_at_required' => 'موعد المتابعة القادمة مطلوب.',
        'cannot_save_client' => 'لا يمكن حفظ العميل',
        'client_updated' => 'تم تحديث العميل بنجاح',
        'opportunity_created' => 'تم إنشاء الفرصة بنجاح',
        'client_number' => 'عميل #:id',
        'name_ar_taken' => 'الاسم بالعربية مرتبط بحساب عميل (:name)',
        'name_en_taken' => 'الاسم بالإنجليزية مرتبط بحساب عميل (:name)',
        'phone_taken' => 'رقم الهاتف مرتبط بحساب عميل (:name)',
        'email_taken' => 'البريد الإلكتروني مرتبط بحساب عميل (:name)',
    ),
    'summaries' => 
    array (
        'total_amount' => 'الإجمالي',
    ),
    'widgets' => 
    array (
        'open_opportunities' => 'الفرص المفتوحة',
        'won_opportunities' => 'الفرص الرابحة',
        'lost_opportunities' => 'الفرص الخاسرة',
        'upcoming_follow_ups' => 'متابعات قادمة',
        'overdue_follow_ups' => 'متابعات متأخرة',
        'conversion_rate' => 'نسبة التحويل',
        'follow_ups_pending' => 'متابعات معلّقة',
        'follow_ups_pending_desc' => 'لم تُكتمل بعد',
        'follow_ups_upcoming' => 'قادمة (7 أيام)',
        'follow_ups_upcoming_desc' => 'مجدولة هذا الأسبوع',
        'follow_ups_completed_today' => 'مكتملة اليوم',
        'my_pending_follow_ups' => 'متابعاتي المعلّقة',
        'rescheduled_follow_ups' => 'معاد جدولتها',
        'rescheduled_follow_ups_desc' => 'مرتبطة بمتابعة أصلية',
        'no_data' => 'لا توجد بيانات',
        'commissions_heading' => 'نظرة عامة على العمولات',
        'commission_pending' => 'عمولات قيد المراجعة',
        'commission_approved' => 'عمولات معتمدة',
        'commission_paid' => 'عمولات مدفوعة',
        'commission_partially_paid' => 'مدفوعة جزئيًا: :count',
        'commission_effective' => 'الاستحقاق الفعلي',
        'commission_net_paid' => 'صافي المسدد',
        'commission_remaining' => 'المتبقي',
        'chart_opportunities_by_stage' => 'الفرص حسب المرحلة',
        'chart_opportunities_trend' => 'اتجاه الفرص (6 أشهر)',
        'trend_created' => 'منشأة',
        'trend_won' => 'رابحة',
        'chart_commissions_by_status' => 'العمولات حسب الحالة',
        'chart_clients_by_source' => 'العملاء حسب المصدر',
    ),
    'enums' => 
    array (
        'commission_status' => 
        array (
            'draft' => 'مسودة',
            'pending' => 'قيد الاعتماد',
            'approved' => 'معتمدة',
            'partially_paid' => 'مسددة جزئياً',
            'paid' => 'مسددة',
            'rejected' => 'مرفوضة',
            'cancelled' => 'ملغاة',
        ),
        'commission_type' => 
        array (
            'sales' => 'مبيعات',
            'referral' => 'إحالة',
            'bonus' => 'مكافأة',
            'override' => 'تجاوز',
            'adjustment' => 'تسوية',
        ),
        'commission_adjustment_direction' => 
        array (
            'increase' => 'زيادة',
            'decrease' => 'نقص',
        ),
        'commission_adjustment_status' => 
        array (
            'pending' => 'قيد الانتظار',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغى',
        ),
        'commission_payment_cycle_status' => 
        array (
            'draft' => 'مسودة',
            'pending_approval' => 'قيد الاعتماد',
            'approved' => 'معتمدة',
            'partially_paid' => 'مسددة جزئياً',
            'paid' => 'مسددة',
            'cancelled' => 'ملغاة',
        ),
        'commission_payment_entry_type' => 
        array (
            'payment' => 'دفعة',
            'reversal' => 'عكس',
        ),
    ),
    'commissions' => 
    array (
        'navigation' => 'العمولات',
        'model' => 'عمولة',
        'plural' => 'العمولات',
        'relation_title' => 'العمولات',
        'automatic' => 
        array (
            'notes' => 'تم إنشاء هذه العمولة تلقائيًا عند إغلاق الفرصة كفرصة ناجحة.',
        ),
        'sections' => 
        array (
            'details' => 'تفاصيل العمولة',
            'financial_summary' => 'الملخص المالي',
            'audit_log' => 'سجل التدقيق',
        ),
        'fields' => 
        array (
            'employee' => 'الموظف',
            'commission_type' => 'نوع العمولة',
            'base_amount' => 'المبلغ الأساسي',
            'commission_percentage' => 'نسبة العمولة',
            'commission_amount' => 'قيمة العمولة',
            'paid_amount' => 'المسدد',
            'remaining_amount' => 'المتبقي',
            'due_at' => 'تاريخ الاستحقاق',
            'approved_at' => 'تاريخ الاعتماد',
            'approved_by' => 'اعتمدها',
            'rejection_reason' => 'سبب الرفض',
            'cancellation_reason' => 'سبب الإلغاء',
            'adjustment_delta' => 'قيمة التسوية (+/-)',
            'recalculate_preview' => 'معاينة إعادة الحساب',
            'audit_action' => 'الإجراء',
            'amount_before' => 'المبلغ قبل',
            'amount_after' => 'المبلغ بعد',
        ),
        'actions' => 
        array (
            'submit' => 'إرسال للاعتماد',
            'approve' => 'اعتماد',
            'reject' => 'رفض',
            'cancel' => 'إلغاء',
            'recalculate' => 'إعادة حساب',
            'create_adjustment' => 'إنشاء تسوية',
        ),
        'audit_actions' => 
        array (
            'created' => 'إنشاء',
            'updated' => 'تحديث',
            'submitted' => 'إرسال للاعتماد',
            'approved' => 'اعتماد',
            'rejected' => 'رفض',
            'cancelled' => 'إلغاء',
            'recalculated' => 'إعادة حساب',
            'adjustment_created' => 'إنشاء تسوية',
            'adjustment_approved' => 'اعتماد تسوية',
            'adjustment_rejected' => 'رفض تسوية',
            'adjustment_cancelled' => 'إلغاء تسوية',
        ),
        'adjustments' => 
        array (
            'relation_title' => 'التسويات',
            'empty' => 'لا توجد تسويات بعد.',
            'fields' => 
            array (
                'direction' => 'الاتجاه',
                'amount' => 'القيمة',
                'reason' => 'السبب',
                'balance_before' => 'الرصيد قبل',
                'balance_after' => 'الرصيد بعد',
                'approved_by' => 'اعتمدها',
                'rejected_by' => 'رفضها',
                'approved_at' => 'تاريخ الاعتماد',
                'rejected_at' => 'تاريخ الرفض',
                'rejection_reason' => 'سبب الرفض',
                'original_amount' => 'قيمة العمولة الأصلية',
                'approved_increase_total' => 'تسويات الزيادة المعتمدة',
                'approved_decrease_total' => 'تسويات النقص المعتمدة',
                'effective_amount' => 'قيمة العمولة الفعلية',
                'net_paid_amount' => 'صافي المسدد',
                'remaining_amount' => 'المتبقي',
                'decrease_preview' => 'معاينة النقص',
                'resulting_effective_amount' => 'القيمة الفعلية الناتجة',
                'reversal_adjustment' => 'تسوية عكسية',
            ),
            'actions' => 
            array (
                'create' => 'إنشاء تسوية',
                'approve' => 'اعتماد التسوية',
                'reject' => 'رفض التسوية',
                'cancel' => 'إلغاء التسوية',
            ),
            'notifications' => 
            array (
                'created' => 'تم إنشاء التسوية وهي قيد الانتظار',
                'approved' => 'تم اعتماد التسوية',
                'rejected' => 'تم رفض التسوية',
                'cancelled' => 'تم إلغاء التسوية',
            ),
            'confirmations' => 
            array (
                'cancel' => 'سيتم إلغاء هذه التسوية المعلقة ولن تؤثر على رصيد العمولة.',
                'approve' => 'راجع القيم الفعلية قبل اعتماد هذه التسوية.',
            ),
            'messages' => 
            array (
                'decrease_preview' => 'الفعلي الحالي: :current | النقص: :amount | الفعلي المتوقع: :projected | صافي المسدد: :net_paid',
            ),
            'hints' => 
            array (
                'amount_positive' => 'أدخل قيمة موجبة. الاتجاه يحدد الزيادة أو النقص.',
            ),
            'errors' => 
            array (
                'already_processed' => 'تمت معالجة هذه التسوية مسبقاً.',
            ),
        ),
        'notifications' => 
        array (
            'submitted' => 'تم إرسال العمولة للاعتماد',
            'approved' => 'تم اعتماد العمولة',
            'rejected' => 'تم رفض العمولة',
            'cancelled' => 'تم إلغاء العمولة',
            'recalculated' => 'تمت إعادة حساب العمولة',
            'adjustment_created' => 'تمت إضافة تسوية',
        ),
        'confirmations' => 
        array (
            'recalculate' => 'سيتم تحديث المبلغ الأساسي من الفرصة وإعادة حساب قيمة العمولة.',
        ),
        'messages' => 
        array (
            'recalculate_preview' => 'أساسي: :old_base → :new_base | قيمة: :old_amount → :new_amount | نسبة: :percentage%',
        ),
        'hints' => 
        array (
            'adjustment_delta' => 'أدخل قيمة موجبة أو سالبة لتعديل قيمة العمولة المعتمدة.',
        ),
        'errors' => 
        array (
            'adjustment_disabled' => 'تسويات العمولة معطّلة حتى يُنفَّذ سجل تسوية مالي مستقل.',
            'adjustment_not_pending' => 'هذه التسوية لم تعد قيد الانتظار.',
            'payment_already_reversed' => 'تم عكس هذه الدفعة مسبقاً.',
        ),
        'validation' => 
        array (
            'base_amount_zero' => 'المبلغ الأساسي صفر — لا يمكن حساب النسبة من القيمة.',
            'base_amount_negative' => 'المبلغ الأساسي لا يمكن أن يكون سالباً.',
            'negative_value' => 'القيمة لا يمكن أن تكون سالبة.',
            'cannot_derive_percentage_from_zero_base' => 'لا يمكن اشتقاق النسبة عندما يكون المبلغ الأساسي صفراً.',
            'percentage_over_limit' => 'النسبة تتجاوز الحد الأقصى المسموح (:max%).',
            'only_draft_can_be_submitted' => 'يمكن إرسال المسودات فقط للاعتماد.',
            'rejection_reason_required' => 'سبب الرفض مطلوب.',
            'cancellation_reason_required' => 'سبب الإلغاء مطلوب.',
            'cannot_cancel_with_payments' => 'لا يمكن إلغاء عمولة لها دفعات مسجلة.',
            'adjustment_notes_required' => 'ملاحظات التسوية مطلوبة.',
            'adjustment_reason_required' => 'سبب التسوية مطلوب.',
            'adjustment_rejection_reason_required' => 'سبب الرفض مطلوب.',
            'adjustment_amount_must_be_positive' => 'قيمة التسوية يجب أن تكون أكبر من صفر.',
            'adjustment_decrease_below_net_paid' => 'التخفيض يجعل الاستحقاق أقل من صافي المسدد.',
            'adjustment_negative_effective' => 'التسوية ستؤدي إلى قيمة عمولة سالبة.',
            'payment_amount_must_be_positive' => 'يجب أن يكون مبلغ السداد أكبر من صفر.',
            'payment_amount_exceeds_remaining' => 'مبلغ السداد يتجاوز الرصيد المتبقي للعمولة.',
            'cycle_not_payable' => 'دورة السداد غير جاهزة لتنفيذ الدفعات.',
            'cycle_has_no_allocations' => 'لا توجد تخصيصات عمولات في دورة السداد.',
            'cycle_allocations_required' => 'مطلوب تخصيص عمولة واحد على الأقل.',
            'only_draft_cycle_can_be_edited' => 'يمكن تعديل دورات السداد في حالة مسودة فقط.',
            'only_draft_cycle_can_be_submitted' => 'يمكن إرسال دورات السداد في حالة مسودة فقط للاعتماد.',
            'only_payments_can_be_reversed' => 'يمكن عكس قيود السداد فقط.',
            'reversal_reason_required' => 'سبب العكس مطلوب.',
            'commission_not_payable' => 'العمولة #:id غير قابلة للسداد في حالتها الحالية.',
            'allocation_user_mismatch' => 'يجب أن يطابق الموظف في التخصيص مالك العمولة.',
            'duplicate_cycle_allocation' => 'لا يمكن تكرار نفس العمولة في دورة السداد.',
        ),
        'empty' => 'لا توجد عمولات بعد.',
    ),
    'payment_cycles' => 
    array (
        'navigation' => 'دورات السداد',
        'model' => 'دورة سداد',
        'plural' => 'دورات السداد',
        'sections' => 
        array (
            'details' => 'تفاصيل الدورة',
            'financial_summary' => 'الملخص المالي',
        ),
        'fields' => 
        array (
            'cycle_number' => 'رقم الدورة',
            'period_from' => 'من تاريخ',
            'period_to' => 'إلى تاريخ',
            'payment_date' => 'تاريخ السداد',
            'payment_method' => 'طريقة السداد',
            'reference_number' => 'رقم المرجع',
            'approved_by' => 'اعتمدها',
            'paid_by' => 'نفّذ السداد',
            'planned_total' => 'الإجمالي المخطط',
            'total_paid' => 'إجمالي المدفوع',
            'total_reversed' => 'إجمالي المعكوس',
            'net_paid' => 'صافي المدفوع',
            'employee_scope' => 'نطاق الموظفين',
            'employees' => 'الموظفون',
            'commissions' => 'العمولات',
            'payment_mode' => 'نمط السداد',
            'planned_payment_amount' => 'مبلغ السداد المخطط',
            'amount' => 'المبلغ',
            'entry_type' => 'نوع القيد',
            'executed_at' => 'تاريخ التنفيذ',
            'executed_by' => 'نفّذها',
            'cancellation_reason' => 'سبب الإلغاء',
            'reversal_reason' => 'سبب العكس',
        ),
        'actions' => 
        array (
            'create' => 'إنشاء دورة سداد',
            'submit' => 'إرسال للاعتماد',
            'approve' => 'اعتماد',
            'execute_payment' => 'تنفيذ السداد',
            'cancel' => 'إلغاء الدورة',
            'reverse_payment' => 'عكس الدفعة',
        ),
        'modes' => 
        array (
            'single_employee' => 'موظف واحد',
            'multiple_employees' => 'عدة موظفين',
            'all_employees' => 'جميع الموظفين',
            'full_payment' => 'سداد كامل',
            'partial_payment' => 'سداد جزئي',
        ),
        'wizard' => 
        array (
            'title' => 'إنشاء دورة سداد',
            'previous' => 'السابق',
            'next' => 'التالي',
            'submit' => 'إنشاء الدورة',
            'preview_summary' => 'الملخص',
            'submit_for_approval' => 'إرسال للاعتماد بعد الإنشاء',
            'submit_for_approval_help' => 'اتركه غير محدد للحفظ كمسودة.',
            'no_payable_commissions' => 'لا توجد عمولات قابلة للسداد للفترة والنطاق المحددين.',
            'descriptions' => 
            array (
                'period_scope' => 'حدّد فترة الاستحقاق والفرع والموظفين المشمولين.',
                'payment_amounts' => 'حدّد المبلغ المخطط لسداده من المتبقي لكل عمولة.',
                'payment_details' => 'وسيلة الدفع والمرجع. يتم التنفيذ لاحقًا بعد الاعتماد.',
                'review' => 'راجع الخطة قبل إنشاء الدورة كمسودة.',
            ),
            'callouts' => 
            array (
                'allocation_plan' => 'هذه الخطوة مجرد خطة سداد، ولا يتم تسجيل أي دفعة بعد.',
                'allocation_vs_payment' => 'المبلغ هنا يمثل خطة السداد فقط، ولن يتم تسجيل دفعة فعلية إلا بعد اعتماد الدورة وتنفيذها.',
                'execution_later' => 'يحدث التنفيذ الفعلي لاحقًا بعد اعتماد الدورة.',
            ),
            'commissions_found' => 'تم العثور على :count عمولة قابلة للسداد.',
            'commission_option' => ':employee — :opportunity — متبقي :remaining (استحقاق :due)',
            'steps' => 
            array (
                'period_scope' => 'الفترة والنطاق',
                'select_commissions' => 'اختيار العمولات',
                'payment_amounts' => 'مبالغ السداد',
                'payment_details' => 'تفاصيل السداد',
                'review' => 'مراجعة وإرسال',
            ),
            'preview' => 
            array (
                'period' => 'الفترة: :from ← :to',
                'allocations_count' => 'التخصيصات: :count',
                'planned_total' => 'الإجمالي المخطط: :amount',
                'payment_date' => 'تاريخ السداد: :date',
                'payment_method' => 'طريقة السداد: :method',
            ),
        ),
        'payments' => 
        array (
            'relation_title' => 'المدفوعات',
            'empty' => 'لا توجد مدفوعات مسجلة لهذه الدورة بعد.',
        ),
        'notifications' => 
        array (
            'created' => 'تم إنشاء دورة السداد',
            'submitted' => 'تم إرسال دورة السداد للاعتماد',
            'approved' => 'تم اعتماد دورة السداد',
            'cancelled' => 'تم إلغاء دورة السداد',
            'payments_executed' => 'تم تنفيذ السداد بنجاح',
            'payment_reversed' => 'تم عكس الدفعة',
        ),
        'confirmations' => 
        array (
            'submit' => 'سيتم إرسال هذه المسودة للاعتماد.',
            'approve' => 'اعتماد دورة السداد هذه؟',
            'execute_payment' => 'تنفيذ جميع المدفوعات المخططة لهذه الدورة؟ سيتم إنشاء قيود دائمة.',
        ),
        'messages' => 
        array (
            'payments_executed_count' => 'تم تسجيل :count دفعة.',
        ),
        'validation' => 
        array (
            'partial_not_allowed' => 'السداد الجزئي غير مسموح لدورك.',
            'full_amount_mismatch' => 'مبلغ السداد الكامل يجب أن يساوي الرصيد المتبقي.',
        ),
        'empty' => 'لا توجد دورات سداد بعد.',
    ),
    'reports' => 
    array (
        'common' => 
        array (
            'not_specified' => 'غير محدد',
            'not_applicable' => 'غير متاح',
            'yes' => 'نعم',
            'no' => 'لا',
            'row_count' => 'عدد السجلات',
        ),
        'actions' => 
        array (
            'export' => 'تصدير',
            'print' => 'طباعة التقرير',
        ),
        'print' => 
        array (
            'generated_at' => 'وقت الإنشاء',
            'generated_by' => 'أنشأه',
            'applied_filters' => 'الفلاتر المطبقة',
            'date_basis' => 'أساس التاريخ: :basis',
            'date_range' => 'الفترة: :from — :to',
            'branch' => 'الفرع: :value',
            'employee' => 'الموظف: :value',
            'source' => 'المصدر: :value',
            'client_stage' => 'مرحلة العميل: :value',
            'campaign' => 'الحملة: :value',
            'campaign_status' => 'حالة الحملة: :value',
            'opportunity_stage' => 'مرحلة الفرصة: :value',
            'opportunity_status' => 'حالة الفرصة: :value',
            'client' => 'العميل: :value',
            'opportunity' => 'الفرصة: :value',
            'amount_range' => 'القيمة: :from — :to',
            'has_opportunities' => 'لديه فرص: :value',
            'has_won_opportunity' => 'لديه فرصة ناجحة: :value',
            'follow_up_type' => 'نوع المتابعة: :value',
            'follow_up_status' => 'حالة المتابعة: :value',
            'follow_up_scheduling' => 'الجدولة: :value',
            'row_limit_reached' => 'يعرض أول :max صف',
        ),
        'filters' => 
        array (
            'date_range' => 'الفترة',
            'date_basis' => 'أساس التاريخ',
            'basis_created_at' => 'تاريخ الإنشاء',
            'basis_updated_at' => 'تاريخ التحديث',
            'basis_closed_at' => 'تاريخ الإغلاق',
            'basis_scheduled_at' => 'تاريخ الجدولة',
            'basis_completed_at' => 'تاريخ الإكمال',
            'basis_start_date' => 'تاريخ بدء الحملة',
            'basis_approved_at' => 'تاريخ الاعتماد',
            'basis_client_created_at' => 'تاريخ إنشاء العميل',
            'basis_opportunity_created_at' => 'تاريخ إنشاء الفرصة',
            'from_date' => 'من',
            'to_date' => 'إلى',
            'amount_range' => 'نطاق القيمة',
            'amount_from' => 'من قيمة',
            'amount_to' => 'إلى قيمة',
            'has_opportunities' => 'لديه فرص',
            'has_won_opportunity' => 'لديه فرصة ناجحة',
            'opportunity_status' => 'حالة الفرصة',
            'status_open' => 'مفتوحة',
            'status_won' => 'ناجحة',
            'status_lost' => 'خاسرة',
        ),
        'customer' => 
        array (
            'navigation' => 'تقارير العملاء',
            'title' => 'تقارير العملاء',
            'stats' => 
            array (
                'total_clients' => 'إجمالي العملاء',
                'new_clients' => 'العملاء الجدد',
                'with_opportunities' => 'لديهم فرص',
                'without_opportunities' => 'بدون فرص',
                'with_won_opportunities' => 'لديهم فرص ناجحة',
                'conversion_rate' => 'معدل التحويل',
                'average_opportunities' => 'متوسط عدد الفرص',
            ),
            'columns' => 
            array (
                'opportunities_count' => 'عدد الفرص',
                'won_opportunities_count' => 'الفرص الناجحة',
                'agreed_amount_total' => 'إجمالي agreed amount',
                'last_follow_up' => 'آخر متابعة',
            ),
            'charts' => 
            array (
                'by_stage' => 'العملاء حسب المرحلة',
            ),
            'export' => 
            array (
                'name' => 'تصدير تقرير العملاء',
                'completed' => 'تم تصدير :count سجل.',
            ),
        ),
        'source' => 
        array (
            'navigation' => 'تقارير المصادر',
            'title' => 'تقارير المصادر',
            'stats' => 
            array (
                'clients_total' => 'إجمالي العملاء',
                'opportunities_total' => 'إجمالي الفرص',
                'open_total' => 'مفتوحة',
                'won_total' => 'ناجحة',
                'lost_total' => 'خاسرة',
                'amount_total' => 'إجمالي amount',
                'agreed_amount_total' => 'إجمالي agreed amount',
                'conversion_rate' => 'معدل التحويل',
            ),
            'columns' => 
            array (
                'source' => 'المصدر',
                'clients_count' => 'العملاء',
                'opportunities_count' => 'الفرص',
                'open_count' => 'مفتوحة',
                'won_count' => 'ناجحة',
                'lost_count' => 'خاسرة',
                'conversion_rate' => 'معدل التحويل',
                'average_amount' => 'متوسط قيمة الفرصة',
            ),
            'export' => 
            array (
                'name' => 'تصدير تقرير المصادر',
                'completed' => 'تم تصدير :count سجل.',
            ),
        ),
        'opportunity' => 
        array (
            'navigation' => 'تقارير الفرص',
            'title' => 'تقارير الفرص',
            'stats' => 
            array (
                'total' => 'إجمالي الفرص',
                'open' => 'مفتوحة',
                'won' => 'ناجحة',
                'lost' => 'خاسرة',
                'amount_total' => 'إجمالي amount',
                'agreed_amount_total' => 'إجمالي agreed amount',
                'close_rate' => 'معدل الإغلاق',
                'success_rate' => 'معدل النجاح',
                'average_close_days' => 'متوسط مدة الإغلاق (أيام)',
            ),
            'columns' => 
            array (
                'close_duration' => 'مدة الإغلاق (أيام)',
                'last_follow_up' => 'آخر متابعة',
                'follow_ups_count' => 'المتابعات',
            ),
            'charts' => 
            array (
                'by_stage' => 'الفرص حسب المرحلة',
            ),
            'export' => 
            array (
                'name' => 'تصدير تقرير الفرص',
                'completed' => 'تم تصدير :count سجل.',
            ),
        ),
        'followup' => 
        array (
            'navigation' => 'تقارير المتابعات',
            'title' => 'تقارير المتابعات',
            'stats' => 
            array (
                'total' => 'إجمالي المتابعات',
                'scheduled' => 'المجدولة',
                'completed' => 'المكتملة',
                'overdue' => 'المتأخرة',
                'completed_on_time' => 'المكتملة في موعدها',
                'average_per_opportunity' => 'متوسط المتابعات لكل فرصة',
                'opportunities_without_follow_up' => 'فرص بلا متابعة',
                'clients_without_follow_up' => 'عملاء بلا متابعة',
            ),
            'columns' => 
            array (
                'scheduling_state' => 'حالة الجدولة',
            ),
            'filters' => 
            array (
                'scheduling' => 'الجدولة',
            ),
            'scheduling' => 
            array (
                'scheduled' => 'مجدولة',
                'overdue' => 'متأخرة',
                'completed' => 'مكتملة',
            ),
            'charts' => 
            array (
                'by_employee' => 'المتابعات حسب الموظف',
                'by_type' => 'المتابعات حسب النوع',
                'by_status' => 'المتابعات حسب الحالة',
            ),
            'export' => 
            array (
                'name' => 'تصدير تقرير المتابعات',
                'completed' => 'تم تصدير :count سجل.',
            ),
        ),
        'campaign' => 
        array (
            'navigation' => 'تقارير الحملات',
            'title' => 'تقارير الحملات',
            'stats' => 
            array (
                'campaigns_count' => 'الحملات',
                'opportunities_total' => 'إجمالي الفرص',
                'won_total' => 'ناجحة',
                'lost_total' => 'خاسرة',
                'amount_total' => 'إجمالي amount',
                'agreed_amount_total' => 'إجمالي agreed amount',
                'conversion_rate' => 'معدل التحويل',
                'expected_roi' => 'Expected ROI',
            ),
            'columns' => 
            array (
                'campaign' => 'الحملة',
                'budget' => 'الميزانية',
                'opportunities_count' => 'الفرص',
                'won_count' => 'ناجحة',
                'lost_count' => 'خاسرة',
                'conversion_rate' => 'معدل التحويل',
                'cost_per_opportunity' => 'تكلفة الفرصة',
                'cost_per_won' => 'تكلفة الفرصة الناجحة',
                'expected_roi' => 'Expected ROI',
            ),
            'charts' => 
            array (
                'by_status' => 'الحملات حسب الحالة',
            ),
            'export' => 
            array (
                'name' => 'تصدير تقرير الحملات',
                'completed' => 'تم تصدير :count سجل.',
            ),
        ),
        'employee' => 
        array (
            'navigation' => 'تقارير الموظفين',
            'title' => 'تقارير أداء الموظفين',
            'stats' => 
            array (
                'employees_count' => 'الموظفون',
                'clients_total' => 'العملاء',
                'opportunities_total' => 'الفرص',
                'conversion_rate' => 'معدل التحويل',
                'average_close_days' => 'متوسط مدة الإغلاق (أيام)',
                'completed_follow_ups' => 'متابعات مكتملة',
                'overdue_follow_ups' => 'متابعات متأخرة',
                'effective_commissions_total' => 'العمولات الفعالة',
                'net_paid_total' => 'صافي المدفوع',
                'remaining_total' => 'المتبقي',
            ),
            'columns' => 
            array (
                'employee' => 'الموظف',
                'clients' => 'العملاء',
                'opportunities' => 'الفرص',
                'open' => 'مفتوحة',
                'won' => 'ناجحة',
                'lost' => 'خاسرة',
                'conversion_rate' => 'معدل التحويل',
                'average_close_days' => 'متوسط مدة الإغلاق (أيام)',
                'completed_follow_ups' => 'متابعات مكتملة',
                'overdue_follow_ups' => 'متابعات متأخرة',
                'effective_commissions' => 'العمولات الفعالة',
                'net_paid' => 'صافي المدفوع',
                'remaining' => 'المتبقي',
            ),
            'rankings' => 
            array (
                'title' => 'أفضل الموظفين',
                'by_won' => 'حسب الفرص الناجحة',
                'by_agreed_amount' => 'حسب إجمالي agreed amount',
                'by_conversion' => 'حسب معدل التحويل',
                'by_follow_up_completion' => 'حسب إكمال المتابعات',
            ),
            'export' => 
            array (
                'name' => 'تصدير تقرير أداء الموظفين',
                'completed' => 'تم تصدير :count سجل.',
            ),
        ),
        'errors' => 
        array (
            'unauthorized' => 'غير مسموح لك بعرض هذا التقرير.',
        ),
    ),
    'own_commissions' => 
    array (
        'navigation' => 'عمولاتي',
        'detail_title' => 'عمولة — :opportunity',
        'sections' => 
        array (
            'details' => 'تفاصيل العمولة',
            'adjustments' => 'التسويات',
            'payments' => 'الدفعات',
            'audit' => 'سجل الحالات',
        ),
        'fields' => 
        array (
            'opportunity_number' => 'رقم الفرصة',
            'original_amount' => 'قيمة العمولة الأصلية',
            'increase_adjustments' => 'زيادات معتمدة',
            'decrease_adjustments' => 'تخفيضات معتمدة',
            'effective_amount' => 'الاستحقاق الفعلي',
            'net_paid' => 'صافي المسدد',
            'remaining' => 'المتبقي',
            'approved_at' => 'تاريخ الاعتماد',
            'due_at' => 'تاريخ الاستحقاق',
            'cycle_number' => 'رقم الدورة',
            'remaining_before' => 'المتبقي قبل',
            'remaining_after' => 'المتبقي بعد',
        ),
        'totals' => 
        array (
            'original' => 'إجمالي العمولات الأصلية',
            'effective' => 'إجمالي الاستحقاق الفعلي',
            'net_paid' => 'إجمالي صافي المسدد',
            'remaining' => 'إجمالي المتبقي',
            'increase_adjustments' => 'إجمالي زيادات التسويات',
            'decrease_adjustments' => 'إجمالي تخفيضات التسويات',
            'pending_count' => 'عمولات قيد المراجعة',
            'opportunity_count' => 'فرص حصلت منها على عمولة',
        ),
        'filters' => 
        array (
            'date_range' => 'الفترة',
            'date_basis' => 'أساس التاريخ',
            'basis_created_at' => 'تاريخ الإنشاء',
            'basis_approved_at' => 'تاريخ الاعتماد',
            'basis_due_at' => 'تاريخ الاستحقاق',
            'from_date' => 'من',
            'to_date' => 'إلى',
            'payment_settlement' => 'حالة السداد',
            'fully_paid' => 'مدفوعة بالكامل',
            'partially_paid' => 'مسددة جزئيًا',
            'unpaid' => 'غير مسددة',
            'include_history' => 'تضمين المرفوضة والملغاة',
        ),
        'actions' => 
        array (
            'export' => 'تصدير عمولاتي',
            'back_to_list' => 'العودة إلى عمولاتي',
        ),
        'export' => 
        array (
            'name' => 'تصدير عمولاتي',
            'completed' => 'تم تصدير :count عمولة.',
        ),
        'empty' => 
        array (
            'list' => 'لا توجد عمولات لعرضها.',
            'adjustments' => 'لا توجد تسويات على هذه العمولة.',
            'payments' => 'لا توجد دفعات مسجلة بعد.',
            'audit' => 'لا يوجد سجل حالات بعد.',
        ),
        'errors' => 
        array (
            'unauthorized' => 'غير مسموح لك بعرض هذه العمولة.',
        ),
    ),
    'timeline' => 
    array (
        'title' => 'الخط الزمني',
        'stage_change' => 'تغيير مرحلة',
        'assignment' => 'تغيير إسناد',
        'note' => 'ملاحظة جديدة',
        'follow_up_created' => 'متابعة مجدولة',
        'follow_up_completed' => 'متابعة مكتملة',
    ),
);
