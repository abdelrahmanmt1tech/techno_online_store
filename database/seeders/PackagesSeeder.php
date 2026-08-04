<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackagesSeeder extends Seeder
{
    public function run(): void
    {
        $egypt = Country::where('country_code', 'EG')->first()
            ?? Country::where('is_active', true)->orderBy('sort_order')->first();

        $egp = Currency::where('code', 'EGP')->first()
            ?? Currency::where('is_active', true)->orderBy('sort_order')->first();

        if (! $egypt || ! $egp) {
            $this->command?->warn('Egypt country or EGP currency not found, skipping packages seed.');

            return;
        }

        // ── Packages with Egypt-only prices ──

        $packageSpecs = [
            [
                'name' => ['ar' => 'باقة المتجر', 'en' => 'Store Package'],
                'desc' => ['ar' => 'متجر إلكتروني متكامل مع كل أساسيات التجارة', 'en' => 'Full online store with all commerce essentials'],
                'module' => 'store',
                'sort' => 1,
                'trials_duration' => 14,
                'price_monthly' => 499,
                'price_yearly' => 4990,
            ],
            [
                'name' => ['ar' => 'باقة نقاط البيع', 'en' => 'POS Package'],
                'desc' => ['ar' => 'نظام نقاط بيع مع سحوبات وكاشير وصرف', 'en' => 'Point of sale with cash drawers, sessions and payouts'],
                'module' => 'pos',
                'sort' => 2,
                'trials_duration' => 14,
                'price_monthly' => 249,
                'price_yearly' => 2490,
            ],
            [
                'name' => ['ar' => 'باقة إدارة العملاء', 'en' => 'CRM Package'],
                'desc' => ['ar' => 'عملاء، مصادر عملاء، وموردون', 'en' => 'Clients, lead sources and suppliers'],
                'module' => 'crm',
                'sort' => 3,
                'trials_duration' => 14,
                'price_monthly' => 399,
                'price_yearly' => 3990,
            ],
            [
                'name' => ['ar' => 'باقة المحاسبة', 'en' => 'Accounting Package'],
                'desc' => ['ar' => 'محاسبة القيد المزدوج مع الترحيل التلقائي', 'en' => 'Double-entry accounting with auto journal posting'],
                'module' => 'accounting',
                'sort' => 4,
                'trials_duration' => 14,
                'price_monthly' => 649,
                'price_yearly' => 6490,
            ],
            [
                'name' => ['ar' => 'الباقة الشاملة', 'en' => 'Full Package'],
                'desc' => ['ar' => 'كل الوحدات: المتجر، نقاط البيع، إدارة العملاء، والمحاسبة', 'en' => 'All modules: store, POS, CRM and accounting'],
                'module' => null,
                'is_full_package' => true,
                'sort' => 5,
                'trials_duration' => 14,
                'price_monthly' => 1499,
                'price_yearly' => 14990,
            ],
        ];

        foreach ($packageSpecs as $spec) {
            $package = Package::updateOrCreate(
                [
                    'module' => $spec['module'] ?? null,
                    'is_full_package' => $spec['is_full_package'] ?? false,
                ],
                [
                    'name' => $spec['name'],
                    'desc' => $spec['desc'] ?? null,
                    'trials_duration' => $spec['trials_duration'] ?? 14,
                    'sort' => $spec['sort'] ?? 0,
                    'is_active' => true,
                ]
            );

            $package->prices()->delete();

            $package->prices()->create([
                'country_id' => $egypt->id,
                'currency_id' => $egp->id,
                'price_monthly' => $spec['price_monthly'],
                'price_yearly' => $spec['price_yearly'],
                'is_default' => true,
            ]);
        }

        $this->command?->info('Seeded '.count($packageSpecs).' packages with Egypt-only prices.');
    }
}
