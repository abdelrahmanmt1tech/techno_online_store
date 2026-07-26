<?php

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function tenantPermissionsArray(): array
{
    return [

        // ══════════════════════════════════════════════
        // ══ بدون جروب ══
        // ══════════════════════════════════════════════

        // ── Categories (Sort: 10) ──
        [
            'name' => 'dashboard.categories',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'categories.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'categories.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'categories.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'categories.delete'],
            ],
        ],

        // ── Products (Sort: 20) ──
        [
            'name' => 'dashboard.products',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'products.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'products.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'products.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'products.delete'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: WhatsApp ══
        // ══════════════════════════════════════════════

        // ── Connect WhatsApp (Sort: 39) ──
        [
            'name' => 'dashboard.whatsapp_connect',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.manage_numbers'],
            ],
        ],

        // ── WhatsApp Numbers (Sort: 40) ──
        [
            'name' => 'dashboard.whatsapp_numbers',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_numbers'],
                ['name' => 'dashboard.permissions.update', 'key' => 'whatsapp.manage_numbers'],
                ['name' => 'dashboard.permissions.update', 'key' => 'whatsapp.switch_reply_number'],
            ],
        ],

        // ── WhatsApp Inbox (Sort: 41) ──
        [
            'name' => 'dashboard.whatsapp_inbox',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_inbox'],
                ['name' => 'dashboard.permissions.create', 'key' => 'whatsapp.send_messages'],
            ],
        ],

        // ── WhatsApp Templates (Sort: 42) ──
        [
            'name' => 'dashboard.whatsapp_templates',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_templates'],
                ['name' => 'dashboard.permissions.create', 'key' => 'whatsapp.manage_templates'],
                ['name' => 'dashboard.permissions.create', 'key' => 'whatsapp.send_template_messages'],
            ],
        ],

        // ── WhatsApp Contacts (Sort: 43) ──
        [
            'name' => 'dashboard.whatsapp_contacts',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_inbox'],
            ],
        ],

        // ── WhatsApp Webhook Events (Sort: 44) ──
        [
            'name' => 'dashboard.whatsapp_webhook_events',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_webhook_events'],
            ],
        ],

        // ── WhatsApp API Log (Sort: 45) ──
        [
            'name' => 'dashboard.whatsapp_api_requests',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.view_inbox'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Messenger ══
        // ══════════════════════════════════════════════

        // ── Connect Messenger (Sort: 49) ──
        [
            'name' => 'dashboard.messenger_connect',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.manage_pages'],
            ],
        ],

        // ── Facebook Pages (Sort: 50) ──
        [
            'name' => 'dashboard.messenger_pages',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.view_pages'],
                ['name' => 'dashboard.permissions.update', 'key' => 'messenger.manage_pages'],
            ],
        ],

        // ── Messenger Inbox (Sort: 51) ──
        [
            'name' => 'dashboard.messenger_inbox',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.view_inbox'],
                ['name' => 'dashboard.permissions.create', 'key' => 'messenger.send_messages'],
            ],
        ],

        // ── Messenger Webhook Events (Sort: 52) ──
        [
            'name' => 'dashboard.messenger_webhook_events',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.view_webhook_events'],
            ],
        ],

        // ── Messenger API Log (Sort: 53) ──
        [
            'name' => 'dashboard.messenger_api_requests',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.view_inbox'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Store ══
        // ══════════════════════════════════════════════

        // ── Governorates (Sort: 50) ──
        [
            'name' => 'dashboard.governorates',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'governorates.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'governorates.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'governorates.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'governorates.delete'],
            ],
        ],

        // ── Orders (Sort: 51) ──
        [
            'name' => 'dashboard.orders',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'orders.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'orders.update'],
            ],
        ],

        // ── Coupons (Sort: 52) ──
        [
            'name' => 'dashboard.coupons',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'coupons.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'coupons.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'coupons.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'coupons.delete'],
            ],
        ],

        // ── Customers (Sort: 55) ──
        [
            'name' => 'dashboard.customers',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'customers.view'],
            ],
        ],

        // ── Reviews (Sort: 55) ──
        [
            'name' => 'dashboard.reviews',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'reviews.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'reviews.update'],
            ],
        ],

        // ── Contact Messages (Sort: 190) ──
        [
            'name' => 'dashboard.contacts',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'contacts.view'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'contacts.delete'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Site Content ══
        // ══════════════════════════════════════════════

        // ── Home Section Builder (Sort: 70) ──
        [
            'name' => 'dashboard.home_section_builder',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'settings.home_sections.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'settings.home_sections.update'],
            ],
        ],

        // ── Pages (Sort: 75) ──
        [
            'name' => 'dashboard.pages',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'pages.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'pages.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'pages.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'pages.delete'],
            ],
        ],

        // ── Contact Us Settings (Sort: 80) ──
        [
            'name' => 'dashboard.nav_contact_us',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'settings.contact_us.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'settings.contact_us.update'],
            ],
        ],

        // ── Footer Settings (Sort: 210) ──
        [
            'name' => 'dashboard.nav_footer',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'footer-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'footer-settings.update'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Themes ══
        // ══════════════════════════════════════════════

        // ── Browse Themes (Sort: 60) ──
        [
            'name' => 'dashboard.browse_themes',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'themes.browse'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Settings ══
        // ══════════════════════════════════════════════

        // ── General Settings (Sort: 200) ──
        [
            'name' => 'dashboard.nav_general',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'settings.general.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'settings.general.update'],
            ],
        ],

        // ── Code Settings (Sort: 210) ──
        [
            'name' => 'dashboard.code_settings_page',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'code-settings.view'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: ERP Settings ══
        // ══════════════════════════════════════════════

        // ── Branches (Sort: 300) ──
        [
            'name' => 'erp.resources.branches',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.branches.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.branches.manage'],
            ],
        ],

        // ── Warehouses (Sort: 301) ──
        [
            'name' => 'erp.resources.warehouses',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.warehouses.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.warehouses.manage'],
            ],
        ],

        // ── Units of Measure (Sort: 302) ──
        [
            'name' => 'erp.resources.units_of_measure',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.uom.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.uom.manage'],
            ],
        ],

        // ── Inventory Items (Sort: 303) ──
        [
            'name' => 'erp.resources.inventory_items',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.inventory.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.inventory.manage'],
            ],
        ],

        // ── Suppliers (Sort: 304) ──
        [
            'name' => 'erp.resources.suppliers',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.suppliers.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.suppliers.manage'],
            ],
        ],

        // ── Invoice Print Settings (Sort: 340) ──
        [
            'name' => 'erp.resources.invoice_print_settings',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.invoice_print.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.invoice_print.manage'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Inventory ══
        // ══════════════════════════════════════════════

        // ── Stock Receipts (Sort: 310) ──
        [
            'name' => 'erp.resources.stock_receipts',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.stock_receipts.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.stock_receipts.manage'],
            ],
        ],

        // ── Stock Issues (Sort: 311) ──
        [
            'name' => 'erp.resources.stock_issues',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.stock_issues.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.stock_issues.manage'],
            ],
        ],

        // ── Stock Transfers (Sort: 312) ──
        [
            'name' => 'erp.resources.stock_transfers',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.stock_transfers.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.stock_transfers.manage'],
            ],
        ],

        // ── Stock Adjustments (Sort: 313) ──
        [
            'name' => 'erp.resources.stock_adjustments',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.stock_adjustments.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.stock_adjustments.manage'],
            ],
        ],

        // ── Stock Damages (Sort: 314) ──
        [
            'name' => 'erp.resources.stock_damages',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.stock_damages.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.stock_damages.manage'],
            ],
        ],

        // ── Stock Movements (Sort: 315) ──
        [
            'name' => 'erp.resources.stock_movements',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.stock_movements.view'],
            ],
        ],

        // ── Stock Balances (Sort: 316) ──
        [
            'name' => 'erp.resources.stock_balances',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.stock_balances.view'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Purchases ══
        // ══════════════════════════════════════════════

        // ── Purchase Orders (Sort: 320) ──
        [
            'name' => 'erp.resources.purchase_orders',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.purchase_orders.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.purchase_orders.manage'],
            ],
        ],

        // ── Goods Receipts (Sort: 321) ──
        [
            'name' => 'erp.resources.goods_receipts',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.goods_receipts.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.goods_receipts.manage'],
            ],
        ],

        // ── Purchase Invoices (Sort: 322) ──
        [
            'name' => 'erp.resources.purchase_invoices',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.purchase_invoices.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.purchase_invoices.manage'],
            ],
        ],

        // ── Purchase Returns (Sort: 323) ──
        [
            'name' => 'erp.resources.purchase_returns',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.purchase_returns.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.purchase_returns.manage'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Sales ══
        // ══════════════════════════════════════════════

        // ── Sales (Sort: 330) ──
        [
            'name' => 'erp.resources.sales',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.sales.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.sales.manage'],
            ],
        ],

        // ── Sales Invoices (Sort: 331) ──
        [
            'name' => 'erp.resources.sales_invoices',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.sales_invoices.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.sales_invoices.manage'],
            ],
        ],

        // ── Sales Returns (Sort: 332) ──
        [
            'name' => 'erp.resources.sales_returns',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.sales_returns.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.sales_returns.manage'],
            ],
        ],

        // ── Invoice Payments (Sort: 333) ──
        [
            'name' => 'erp.resources.invoice_payments',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'erp.invoice_payments.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'erp.invoice_payments.manage'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Users ══
        // ══════════════════════════════════════════════

        // ── Roles & Permissions (Sort: 70) ──
        [
            'name' => 'dashboard.roles_and_permissions',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'roles-and-permission.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'roles-and-permission.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'roles-and-permission.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'roles-and-permission.destroy'],
            ],
        ],

        // ── Users (Sort: 80) ──
        [
            'name' => 'dashboard.tenant_users',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'tenant-users.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'tenant-users.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'tenant-users.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'tenant-users.destroy'],
            ],
        ],
    ];
}

function StoreTenantPermissionsArray(): void
{
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permissionsArray = collect(tenantPermissionsArray());
    $newPermissions = collect();

    foreach ($permissionsArray as $group) {
        foreach ($group['permissions'] as $perm) {
            $newPermissions->push([
                'key' => $perm['key'],
                'name' => $perm['name'],
                'group' => $group['name'],
            ]);
        }
    }

    $guard = 'tenant';
    $newKeys = $newPermissions->pluck('key')->unique()->toArray();

    DB::transaction(function () use ($guard, $newKeys, $newPermissions): void {
        $existing = Permission::where('guard_name', $guard)->get();
        $existingKeys = $existing->pluck('name')->toArray();

        $toDelete = array_diff($existingKeys, $newKeys);

        if (! empty($toDelete)) {
            $permissionsToDelete = Permission::whereIn('name', $toDelete)
                ->where('guard_name', $guard)
                ->get(['id']);

            $ids = $permissionsToDelete->pluck('id')->filter()->values()->toArray();

            if (! empty($ids)) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
                Permission::whereIn('id', $ids)->delete();
            }
        }

        foreach ($newPermissions as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm['key'], 'guard_name' => $guard],
                [
                    'display_name' => $perm['name'],
                    'group_name' => $perm['group'],
                ]
            );
        }
    });

    app()[PermissionRegistrar::class]->forgetCachedPermissions();
}

function setupStoreAdminRole(): Role
{
    $role = Role::firstOrCreate([
        'name' => 'Store Admin',
        'guard_name' => 'tenant',
    ]);

    $permissions = collect(tenantPermissionsArray())
        ->flatMap(fn ($group) => collect($group['permissions'])->pluck('key'))
        ->values()
        ->toArray();

    $role->syncPermissions($permissions);

    return $role;
}
