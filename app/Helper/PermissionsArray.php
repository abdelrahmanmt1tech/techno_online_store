<?php

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function permissionsArray(): array
{
    return [

        // ══════════════════════════════════════════════
        // ══ Messaging Operations Group ══
        // ══════════════════════════════════════════════

        // ── Messaging Health Dashboard (Sort: 35) ──
        [
            'name' => 'dashboard.messaging_health',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messaging-health.view'],
            ],
        ],

        // ── Meta Integrations Reset (Sort: 90) ──
        [
            'name' => 'dashboard.meta_reset_nav',
            'permissions' => [
                ['name' => 'dashboard.permissions.delete', 'key' => 'meta.integrations.reset'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ WhatsApp Group ══
        // ══════════════════════════════════════════════

        // ── WhatsApp Numbers (Sort: 40) ──
        [
            'name' => 'dashboard.whatsapp_numbers',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.platform.view_all_numbers'],
                ['name' => 'dashboard.permissions.update', 'key' => 'whatsapp.platform.manage_all_numbers'],
            ],
        ],

        // ── WhatsApp Inbox (Sort: 41) ──
        [
            'name' => 'dashboard.whatsapp_inbox',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.platform.view_all_conversations'],
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.platform.view_all_messages'],
            ],
        ],

        // ── WhatsApp Templates (Sort: 42) ──
        [
            'name' => 'dashboard.whatsapp_templates',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.platform.view_all_templates'],
                ['name' => 'dashboard.permissions.update', 'key' => 'whatsapp.platform.manage_all_templates'],
            ],
        ],

        // ── WhatsApp Webhook Events (Sort: 44) ──
        [
            'name' => 'dashboard.whatsapp_webhook_events',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.platform.view_webhook_events'],
                ['name' => 'dashboard.permissions.update', 'key' => 'whatsapp.platform.manage_webhook_events'],
            ],
        ],

        // ── WhatsApp (troubleshoot, send test) ──
        [
            'name' => 'dashboard.whatsapp_group',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'whatsapp.platform.troubleshoot'],
                ['name' => 'dashboard.permissions.create', 'key' => 'whatsapp.platform.send_test_messages'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ Messenger Group ══
        // ══════════════════════════════════════════════

        // ── Messenger Pages (Sort: 50) ──
        [
            'name' => 'dashboard.messenger_pages',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.platform.view_all_pages'],
                ['name' => 'dashboard.permissions.update', 'key' => 'messenger.platform.manage_all_pages'],
            ],
        ],

        // ── Messenger Inbox (Sort: 51) ──
        [
            'name' => 'dashboard.messenger_inbox',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.platform.troubleshoot'],
            ],
        ],

        // ── Messenger Webhook Events (Sort: 52) ──
        [
            'name' => 'dashboard.messenger_webhook_events',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'messenger.platform.view_webhook_events'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ بدون جروب ══
        // ══════════════════════════════════════════════

        // ── Plans (Sort: 50) ──
        [
            'name' => 'dashboard.permissions_groups.plans',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'plans.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'plans.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'plans.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'plans.delete'],
            ],
        ],

        // ── Tenants (Sort: 60) ──
        [
            'name' => 'dashboard.permissions_groups.tenants',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'tenants.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'tenants.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'tenants.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'tenants.delete'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Users ══
        // ══════════════════════════════════════════════

        // ── Roles & Permissions (Sort: 70) ──
        [
            'name' => 'dashboard.permissions_groups.roles',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'roles-and-permission.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'roles-and-permission.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'roles-and-permission.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'roles-and-permission.destroy'],
            ],
        ],

        // ── Admins (Sort: 80) ──
        [
            'name' => 'dashboard.permissions_groups.admins',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'admins.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'admins.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'admins.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'admins.delete'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Site Content ══
        // ══════════════════════════════════════════════

        // ── Intro Settings (Sort: 85) ──
        [
            'name' => 'dashboard.nav_intro',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'intro-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'intro-settings.update'],
            ],
        ],

        // ── About Settings (Sort: 86) ──
        [
            'name' => 'dashboard.nav_about',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'about-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'about-settings.update'],
            ],
        ],

        // ── Statistics Settings (Sort: 87) ──
        [
            'name' => 'dashboard.nav_statistics',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'statistics-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'statistics-settings.update'],
            ],
        ],

        // ── AI Services Settings (Sort: 88) ──
        [
            'name' => 'dashboard.nav_ai_services',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'ai-services-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'ai-services-settings.update'],
            ],
        ],

        // ── Categories (Sort: 90) ──
        [
            'name' => 'dashboard.categories',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'categories.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'categories.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'categories.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'categories.delete'],
            ],
        ],

        // ── Themes (Sort: 100) ──
        [
            'name' => 'dashboard.themes',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'themes.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'themes.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'themes.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'themes.delete'],
            ],
        ],

        // ── Payment Gateways Settings (Sort: 110) ──
        [
            'name' => 'dashboard.nav_payment_gateways',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'payment-gateways-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'payment-gateways-settings.update'],
            ],
        ],

        // ── Shipping Companies Settings (Sort: 120) ──
        [
            'name' => 'dashboard.nav_shipping_companies',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'shipping-companies-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'shipping-companies-settings.update'],
            ],
        ],

        // ── Marketing Channels Settings (Sort: 130) ──
        [
            'name' => 'dashboard.nav_marketing_channels',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'marketing-channels-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'marketing-channels-settings.update'],
            ],
        ],

        // ── Training Support Settings (Sort: 140) ──
        [
            'name' => 'dashboard.nav_training_support',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'training-support-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'training-support-settings.update'],
            ],
        ],

        // ── FAQs (Sort: 150) ──
        [
            'name' => 'dashboard.faqs',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'faqs.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'faqs.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'faqs.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'faqs.delete'],
            ],
        ],

        // ── Have Question Settings (Sort: 160) ──
        [
            'name' => 'dashboard.nav_have_question',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'have-question-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'have-question-settings.update'],
            ],
        ],

        // ── Contact Us Settings (Sort: 170) ──
        [
            'name' => 'dashboard.nav_contact_us',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'contact-us-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'contact-us-settings.update'],
            ],
        ],

        // ── Footer Settings (Sort: 180) ──
        [
            'name' => 'dashboard.nav_footer',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'footer-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'footer-settings.update'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Blog Management ══
        // ══════════════════════════════════════════════

        // ── Blog Categories (Sort: 180) ──
        [
            'name' => 'dashboard.blog_categories',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'blog-categories.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'blog-categories.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'blog-categories.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'blog-categories.delete'],
            ],
        ],

        // ── Blog Tags (Sort: 185) ──
        [
            'name' => 'dashboard.blog_tags',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'blog-tags.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'blog-tags.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'blog-tags.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'blog-tags.delete'],
            ],
        ],

        // ── Blogs (Sort: 190) ──
        [
            'name' => 'dashboard.blogs',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'blogs.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'blogs.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'blogs.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'blogs.delete'],
            ],
        ],

        // ── Pages (Sort: 160) ──
        [
            'name' => 'dashboard.cms_pages',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'pages.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'pages.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'pages.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'pages.delete'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ بدون جروب ══
        // ══════════════════════════════════════════════

        // ── Contacts (Sort: 190) ──
        [
            'name' => 'dashboard.permissions_groups.contacts',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'contacts.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'contacts.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'contacts.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'contacts.delete'],
            ],
        ],

        // ══════════════════════════════════════════════
        // ══ جروب: Settings ══
        // ══════════════════════════════════════════════

        // ── General Settings (Sort: 200) ──
        [
            'name' => 'dashboard.nav_general',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'general-settings.view'],
                ['name' => 'dashboard.permissions.update', 'key' => 'general-settings.update'],
            ],
        ],

        // ── Countries (Sort: 205) ──
        [
            'name' => 'dashboard.countries',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'countries.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'countries.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'countries.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'countries.delete'],
            ],
        ],

        // ── Currencies (Sort: 206) ──
        [
            'name' => 'dashboard.currencies',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'currencies.view'],
                ['name' => 'dashboard.permissions.create', 'key' => 'currencies.create'],
                ['name' => 'dashboard.permissions.update', 'key' => 'currencies.update'],
                ['name' => 'dashboard.permissions.delete', 'key' => 'currencies.delete'],
            ],
        ],

        // ── Code Settings (Sort: 210) ──
        [
            'name' => 'dashboard.code_settings_page',
            'permissions' => [
                ['name' => 'dashboard.permissions.view', 'key' => 'code-settings.view'],
            ],
        ],

    ];
}

function StorePermissionsArray()
{
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permissionsArray = collect(permissionsArray());

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

    $guard = 'admin';
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
