<?php

return [
    'nav' => [
        'commerce' => 'Commerce',
        'pos' => 'POS',
    ],

    'catalog_product_types' => [
        'inventory_item' => 'Inventory Item',
        'service' => 'Service',
        'digital' => 'Digital',
        'bundle' => 'Bundle',
        'raw_material' => 'Raw Material',
        'non_stock_item' => 'Non-Stock Item',
    ],

    'product_statuses' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'archived' => 'Archived',
    ],

    'product_visibilities' => [
        'visible' => 'Visible',
        'hidden' => 'Hidden',
        'catalog_only' => 'Catalog Only',
        'pos_only' => 'POS Only',
    ],

    'sale_channels' => [
        'store' => 'Online Store',
        'erp' => 'ERP',
        'pos' => 'POS',
        'api' => 'API',
    ],

    'resources' => [
        'brand' => 'Brand',
        'brands' => 'Brands',
        'attribute' => 'Attribute',
        'attributes' => 'Attributes',
        'pos_register' => 'POS Register',
        'pos_registers' => 'POS Registers',
        'pos_payment_method' => 'POS Payment Method',
        'pos_payment_methods' => 'POS Payment Methods',
        'pos_setting' => 'POS Settings',
        'pos_settings' => 'POS Settings',
        'cashier_session' => 'Cashier Session',
        'cashier_sessions' => 'Cashier Sessions',
        'cash_drawer' => 'Cash Drawer',
        'cash_drawers' => 'Cash Drawers',
    ],

    'fields' => [
        'brand' => 'Brand',
        'catalog_type' => 'Product Type',
        'status' => 'Status',
        'visibility' => 'Visibility',
        'barcode' => 'Barcode',
        'unit' => 'Unit of Measure',
        'allow_backorders' => 'Allow Backorders',
        'low_stock_alert' => 'Low Stock Alert',
        'tax_class' => 'Tax Class',
        'weight' => 'Weight',
        'dimensions' => 'Dimensions',
        'notes' => 'Notes',
        'opening_balance' => 'Opening Balance',
        'closing_balance' => 'Closing Balance',
        'expected_balance' => 'Expected Balance',
        'actual_balance' => 'Actual Balance',
        'difference' => 'Difference',
        'opening_notes' => 'Opening Notes',
        'closing_notes' => 'Closing Notes',
        'difference_reason' => 'Difference Reason',
        'device_name' => 'Device',
        'register' => 'Register',
        'warehouse' => 'Warehouse',
        'cash_drawer' => 'Cash Drawer',
        'receipt_prefix' => 'Receipt Prefix',
        'slug' => 'Slug',
        'logo' => 'Logo',
        'sort_order' => 'Sort Order',
        'type' => 'Type',
        'opens_cash_drawer' => 'Opens Cash Drawer',
        'receipt_number_strategy' => 'Receipt Number Strategy',
        'require_open_session' => 'Require Open Session',
        'allow_suspend_sales' => 'Allow Suspend Sales',
        'allow_negative_stock' => 'Allow Negative Stock',
        'default_currency' => 'Default Currency',
        'cashier' => 'Cashier',
        'opened_at' => 'Opened At',
        'closed_at' => 'Closed At',
    ],

    'pos_payment_types' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'transfer' => 'Transfer',
        'other' => 'Other',
    ],

    'receipt_number_strategies' => [
        'per_register' => 'Per Register',
        'global' => 'Global',
    ],

    'validation' => [
        'cashier_session_required' => 'An open cashier session is required before selling on POS.',
        'cashier_session_already_open' => 'This register already has an open cashier session.',
        'sale_not_suspended' => 'Sale is not suspended.',
        'sale_already_suspended' => 'Sale is already suspended.',
        'only_draft_can_suspend' => 'Only draft sales can be suspended.',
        'bundle_stock_not_deducted' => 'Bundle stock deduction is not implemented in this phase.',
    ],

    'session_statuses' => [
        'open' => 'Open',
        'closed' => 'Closed',
    ],

    'cash_movement_types' => [
        'cash_in' => 'Cash In',
        'cash_out' => 'Cash Out',
        'safe_drop' => 'Safe Drop',
        'pay_in' => 'Pay In',
        'pay_out' => 'Pay Out',
        'opening' => 'Opening',
        'closing' => 'Closing',
    ],
];
