<?php

return [
    'nav' => [
        'commerce' => 'التجارة',
        'pos' => 'نقطة البيع',
    ],

    'catalog_product_types' => [
        'inventory_item' => 'صنف مخزني',
        'service' => 'خدمة',
        'digital' => 'رقمي',
        'bundle' => 'حزمة',
        'raw_material' => 'مادة خام',
        'non_stock_item' => 'صنف غير مخزني',
    ],

    'product_statuses' => [
        'draft' => 'مسودة',
        'active' => 'نشط',
        'archived' => 'مؤرشف',
    ],

    'product_visibilities' => [
        'visible' => 'ظاهر',
        'hidden' => 'مخفي',
        'catalog_only' => 'الكتالوج فقط',
        'pos_only' => 'نقطة البيع فقط',
    ],

    'sale_channels' => [
        'store' => 'المتجر الإلكتروني',
        'erp' => 'نظام ERP',
        'pos' => 'نقطة البيع',
        'api' => 'واجهة API',
    ],

    'resources' => [
        'brand' => 'علامة تجارية',
        'brands' => 'العلامات التجارية',
        'attribute' => 'خاصية',
        'attributes' => 'الخصائص',
        'pos_register' => 'صندوق نقطة البيع',
        'pos_registers' => 'صناديق نقطة البيع',
        'pos_payment_method' => 'طريقة دفع نقطة البيع',
        'pos_payment_methods' => 'طرق دفع نقطة البيع',
        'pos_setting' => 'إعدادات نقطة البيع',
        'pos_settings' => 'إعدادات نقطة البيع',
        'cashier_session' => 'وردية كاشير',
        'cashier_sessions' => 'ورديات الكاشير',
        'cash_drawer' => 'درج النقدية',
        'cash_drawers' => 'أدراج النقدية',
    ],

    'fields' => [
        'brand' => 'العلامة التجارية',
        'catalog_type' => 'نوع المنتج',
        'status' => 'الحالة',
        'visibility' => 'الظهور',
        'barcode' => 'الباركود',
        'unit' => 'وحدة القياس',
        'allow_backorders' => 'السماح بالبيع بدون مخزون',
        'low_stock_alert' => 'تنبيه انخفاض المخزون',
        'tax_class' => 'فئة الضريبة',
        'weight' => 'الوزن',
        'dimensions' => 'الأبعاد',
        'notes' => 'ملاحظات',
        'opening_balance' => 'رصيد الافتتاح',
        'closing_balance' => 'رصيد الإغلاق',
        'expected_balance' => 'الرصيد المتوقع',
        'actual_balance' => 'الرصيد الفعلي',
        'difference' => 'فرق الجرد',
        'opening_notes' => 'ملاحظات الافتتاح',
        'closing_notes' => 'ملاحظات الإغلاق',
        'difference_reason' => 'سبب الفرق',
        'device_name' => 'الجهاز',
        'register' => 'الصندوق',
        'warehouse' => 'المستودع',
        'cash_drawer' => 'درج النقدية',
        'receipt_prefix' => 'بادئة الإيصال',
        'slug' => 'المعرف النصي',
        'logo' => 'الشعار',
        'sort_order' => 'الترتيب',
        'type' => 'النوع',
        'opens_cash_drawer' => 'يفتح درج النقدية',
        'receipt_number_strategy' => 'استراتيجية ترقيم الإيصالات',
        'require_open_session' => 'يتطلب فتح وردية',
        'allow_suspend_sales' => 'السماح بتعليق المبيعات',
        'allow_negative_stock' => 'السماح بالمخزون السالب',
        'default_currency' => 'العملة الافتراضية',
        'cashier' => 'الكاشير',
        'opened_at' => 'وقت الفتح',
        'closed_at' => 'وقت الإغلاق',
    ],

    'pos_payment_types' => [
        'cash' => 'نقدي',
        'card' => 'بطاقة',
        'transfer' => 'تحويل',
        'other' => 'أخرى',
    ],

    'receipt_number_strategies' => [
        'per_register' => 'لكل صندوق',
        'global' => 'عام',
    ],

    'validation' => [
        'cashier_session_required' => 'يجب فتح وردية كاشير قبل البيع من نقطة البيع.',
        'cashier_session_already_open' => 'يوجد وردية مفتوحة بالفعل لهذا الصندوق.',
        'sale_not_suspended' => 'المبيعة ليست معلّقة.',
        'sale_already_suspended' => 'المبيعة معلّقة بالفعل.',
        'only_draft_can_suspend' => 'يمكن تعليق مسودات المبيعات فقط.',
        'bundle_stock_not_deducted' => 'خصم مخزون الحزم غير مفعّل في هذه المرحلة.',
    ],

    'session_statuses' => [
        'open' => 'مفتوحة',
        'closed' => 'مغلقة',
    ],

    'cash_movement_types' => [
        'cash_in' => 'إدخال نقدي',
        'cash_out' => 'إخراج نقدي',
        'safe_drop' => 'إيداع خزنة',
        'pay_in' => 'دفعة واردة',
        'pay_out' => 'دفعة صادرة',
        'opening' => 'افتتاح',
        'closing' => 'إغلاق',
    ],
];
