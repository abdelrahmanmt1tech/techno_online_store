<?php

namespace Tests\Feature\Mail;

use App\Mail\TenantPackageMail;
use App\Mail\TenantWelcomeMail;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Tenant;
use App\Models\TenantPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TenantMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');
    }

    private function makePackage(array $overrides = []): Package
    {
        $country = $overrides['country'] ?? Country::create([
            'name' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
            'country_code' => 'SA',
            'currency_code' => 'SAR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $currency = $overrides['currency'] ?? Currency::create([
            'name' => ['ar' => 'ريال', 'en' => 'Saudi Riyal'],
            'code' => 'SAR',
            'symbol' => 'SAR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        unset($overrides['country'], $overrides['currency']);

        $package = Package::create(array_merge([
            'module' => 'store',
            'is_full_package' => false,
            'name' => ['ar' => 'باقة المتجر', 'en' => 'Store Package'],
            'trials_duration' => 0,
            'sort' => 1,
            'is_active' => true,
        ], $overrides));

        PackagePrice::create([
            'package_id' => $package->id,
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'price' => 99.99,
            'duration' => 1,
            'duration_type' => 'month',
        ]);

        return $package->fresh();
    }

    private function makeTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Mail Store',
            'email' => 'store@example.com',
        ]);
    }

    private function makeTenantPackage(Tenant $tenant, Package $package): TenantPackage
    {
        return TenantPackage::create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'price' => 99.99,
            'duration' => 1,
            'duration_type' => 'month',
            'started_at' => now(),
            'status' => 'active',
        ]);
    }

    public function test_welcome_and_added_mails_are_sent_when_tenant_is_created(): void
    {
        Mail::fake();

        $package = $this->makePackage();

        $this->postJson('/api/tenants', [
            'name' => 'Mail Store',
            'email' => 'store@example.com',
            'phone' => '123456789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'mail-store',
            'payment_method' => 'offline',
            'terms_accepted' => true,
            'packages' => [
                ['package_id' => $package->id, 'price_id' => $package->prices()->first()->id],
            ],
            'started_at' => now()->toDateTimeString(),
        ])->assertCreated();

        Mail::assertSent(TenantWelcomeMail::class, function (TenantWelcomeMail $mail) {
            return $mail->email === 'store@example.com'
                && $mail->password === 'secret123'
                && str_contains($mail->loginUrl, 'mail-store');
        });

        Mail::assertSent(TenantPackageMail::class, fn (TenantPackageMail $mail) => $mail->action === 'added');
    }

    public function test_status_change_sends_activation_or_deactivation_mail(): void
    {
        $tenant = $this->makeTenant();
        $package = $this->makePackage();
        $tenantPackage = $this->makeTenantPackage($tenant, $package);

        Mail::fake();

        $tenantPackage->update(['status' => 'cancelled']);
        Mail::assertSent(TenantPackageMail::class, fn (TenantPackageMail $mail) => $mail->action === 'deactivated');

        $tenantPackage->update(['status' => 'active']);
        Mail::assertSent(TenantPackageMail::class, fn (TenantPackageMail $mail) => $mail->action === 'activated');
    }

    public function test_deleting_package_sends_cancellation_mail(): void
    {
        $tenant = $this->makeTenant();
        $package = $this->makePackage();
        $tenantPackage = $this->makeTenantPackage($tenant, $package);

        Mail::fake();

        $tenantPackage->delete();

        Mail::assertSent(TenantPackageMail::class, fn (TenantPackageMail $mail) => $mail->action === 'cancelled');
    }

    public function test_no_mail_sent_when_only_price_changes(): void
    {
        $tenant = $this->makeTenant();
        $package = $this->makePackage();
        $tenantPackage = $this->makeTenantPackage($tenant, $package);

        Mail::fake();

        $tenantPackage->update(['price' => 120.0]);

        Mail::assertNothingSent();
    }
}
