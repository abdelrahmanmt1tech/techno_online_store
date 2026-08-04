<?php

namespace Tests\Feature\Api\Front;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Package;
use App\Models\PackagePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TenantControllerTest extends TestCase
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

    public function test_it_creates_tenant_with_subdomain_and_package(): void
    {
        $package = $this->makePackage();

        $response = $this->postJson('/api/tenants', [
            'name' => 'Test Store',
            'email' => 'store@example.com',
            'phone' => '123456789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'test-store',
            'payment_method' => 'offline',
            'terms_accepted' => true,
            'packages' => [
                ['package_id' => $package->id, 'price_id' => $package->prices()->first()->id],
            ],
            'started_at' => now()->toDateTimeString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'phone', 'domain'],
        ]);
        $response->assertJsonPath('data.name', 'Test Store');
        $response->assertJsonPath('data.email', 'store@example.com');

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Store',
            'email' => 'store@example.com',
            'payment_method' => 'offline',
            'terms_accepted' => true,
        ]);

        $tenantId = $response->json('data.id');
        $this->assertDatabaseHas('tenant_packages', [
            'tenant_id' => $tenantId,
            'package_id' => $package->id,
            'price' => 99.99,
            'duration' => 1,
            'duration_type' => 'month',
            'status' => 'active',
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/tenants', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'password', 'subdomain']);
    }

    public function test_it_validates_email_format(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'email' => 'invalid',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'my-store',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_it_validates_subdomain_format(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'INVALID_UPPERCASE',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['subdomain']);
    }

    public function test_it_validates_password_confirmation(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'different',
            'subdomain' => 'my-store',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_it_validates_payment_method_and_terms_accepted(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'my-store',
            'payment_method' => 'cash-on-delivery',
            'terms_accepted' => 'maybe',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['payment_method', 'terms_accepted']);
    }

    public function test_it_validates_package_exists(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'my-store',
            'packages' => [
                ['package_id' => 999, 'price_id' => 999],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['packages.0.package_id']);
    }

    public function test_it_creates_tenant_with_multiple_packages(): void
    {
        $package1 = $this->makePackage(['sort' => 1]);
        $package2 = $this->makePackage(['module' => 'pos', 'sort' => 2]);

        $response = $this->postJson('/api/tenants', [
            'name' => 'Multi Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'multi-store',
            'payment_method' => 'offline',
            'terms_accepted' => true,
            'packages' => [
                ['package_id' => $package1->id, 'price_id' => $package1->prices()->first()->id],
                ['package_id' => $package2->id, 'price_id' => $package2->prices()->first()->id],
            ],
            'started_at' => now()->toDateTimeString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);

        $tenantId = $response->json('data.id');

        $this->assertDatabaseHas('tenant_packages', [
            'tenant_id' => $tenantId,
            'package_id' => $package1->id,
            'price' => 99.99,
            'duration' => 1,
            'duration_type' => 'month',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tenant_packages', [
            'tenant_id' => $tenantId,
            'package_id' => $package2->id,
            'price' => 99.99,
            'status' => 'active',
        ]);
    }

    public function test_it_requires_price_for_each_package(): void
    {
        $package = $this->makePackage();

        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'my-store',
            'packages' => [
                ['package_id' => $package->id],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['packages.0.price_id']);
    }

    public function test_it_computes_trial_and_expiry_from_package(): void
    {
        $startedAt = now()->startOfDay();
        $package = $this->makePackage(['trials_duration' => 14]);

        $response = $this->postJson('/api/tenants', [
            'name' => 'Trial Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'trial-store',
            'payment_method' => 'offline',
            'terms_accepted' => true,
            'packages' => [
                ['package_id' => $package->id, 'price_id' => $package->prices()->first()->id],
            ],
            'started_at' => $startedAt->toDateTimeString(),
        ]);

        $response->assertCreated();

        $tenantId = $response->json('data.id');

        $this->assertDatabaseHas('tenant_packages', [
            'tenant_id' => $tenantId,
            'package_id' => $package->id,
            'trial_ends_at' => $startedAt->copy()->addDays(14)->toDateTimeString(),
            'expires_at' => $startedAt->copy()->addDays(14)->addMonth()->toDateTimeString(),
        ]);
    }

    public function test_it_uses_selected_price_for_package(): void
    {
        $package = $this->makePackage();

        $firstPrice = $package->prices()->first();

        $selectedPrice = PackagePrice::create([
            'package_id' => $package->id,
            'country_id' => $firstPrice->country_id,
            'currency_id' => $firstPrice->currency_id,
            'price' => 499.99,
            'duration' => 12,
            'duration_type' => 'month',
        ]);

        $response = $this->postJson('/api/tenants', [
            'name' => 'Priced Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'priced-store',
            'payment_method' => 'offline',
            'terms_accepted' => true,
            'packages' => [
                ['package_id' => $package->id, 'price_id' => $selectedPrice->id],
            ],
        ]);

        $response->assertCreated();

        $tenantId = $response->json('data.id');

        $this->assertDatabaseHas('tenant_packages', [
            'tenant_id' => $tenantId,
            'package_id' => $package->id,
            'price' => 499.99,
            'duration' => 12,
            'duration_type' => 'month',
        ]);
    }

    public function test_it_validates_price_belongs_to_selected_package(): void
    {
        $package1 = $this->makePackage(['sort' => 1]);
        $package2 = $this->makePackage(['module' => 'pos', 'sort' => 2]);

        $priceOfPackage2 = $package2->prices()->first();

        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'my-store',
            'packages' => [
                ['package_id' => $package1->id, 'price_id' => $priceOfPackage2->id],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['packages.0.price_id']);
    }

    public function test_packages_endpoint_returns_only_active_packages(): void
    {
        Http::fake();

        $this->makePackage(['module' => 'store', 'sort' => 1]);
        $this->makePackage(['module' => 'pos', 'sort' => 2, 'is_active' => false]);

        $response = $this->getJson('/api/packages');

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $packages = $response->json('data');
        $this->assertCount(1, $packages);
        $this->assertEquals('store', $packages[0]['module']);
        $this->assertCount(1, $packages[0]['prices']);
        $this->assertEquals(99.99, $packages[0]['prices'][0]['price']);
        $this->assertEquals('SAR', $packages[0]['prices'][0]['currency_code']);
        $this->assertEquals('month', $packages[0]['prices'][0]['duration_type']);
    }

    public function test_packages_endpoint_filters_prices_by_detected_country(): void
    {
        Http::fake();

        $sa = Country::create([
            'name' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
            'country_code' => 'SA',
            'currency_code' => 'SAR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $saCurrency = Currency::create([
            'name' => ['ar' => 'ريال', 'en' => 'Saudi Riyal'],
            'code' => 'SAR',
            'symbol' => 'SAR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $eg = Country::create([
            'name' => ['ar' => 'مصر', 'en' => 'Egypt'],
            'country_code' => 'EG',
            'currency_code' => 'EGP',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $egCurrency = Currency::create([
            'name' => ['ar' => 'جنيه', 'en' => 'Egyptian Pound'],
            'code' => 'EGP',
            'symbol' => 'EGP',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $package = $this->makePackage(['country' => $sa, 'currency' => $saCurrency]);

        PackagePrice::create([
            'package_id' => $package->id,
            'country_id' => $eg->id,
            'currency_id' => $egCurrency->id,
            'price' => 150.0,
            'duration' => 1,
            'duration_type' => 'year',
        ]);

        $response = $this->withHeader('CF-IPCountry', 'SA')->getJson('/api/packages');

        $response->assertOk();

        $packages = $response->json('data');
        $this->assertCount(1, $packages);
        $this->assertCount(1, $packages[0]['prices']);
        $this->assertEquals($sa->id, $packages[0]['prices'][0]['country_id']);
        $this->assertEquals('SAR', $packages[0]['prices'][0]['currency_code']);
        $this->assertEquals(99.99, $packages[0]['prices'][0]['price']);
    }

    public function test_packages_endpoint_falls_back_to_all_prices_when_country_unknown(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response(['status' => 'success', 'countryCode' => 'US'], 200),
        ]);

        $sa = Country::create([
            'name' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
            'country_code' => 'SA',
            'currency_code' => 'SAR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $saCurrency = Currency::create([
            'name' => ['ar' => 'ريال', 'en' => 'Saudi Riyal'],
            'code' => 'SAR',
            'symbol' => 'SAR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $package = $this->makePackage(['country' => $sa, 'currency' => $saCurrency]);

        $response = $this->getJson('/api/packages');

        $response->assertOk();

        $packages = $response->json('data');
        $this->assertCount(1, $packages);
        $this->assertCount(1, $packages[0]['prices']);
        $this->assertEquals($sa->id, $packages[0]['prices'][0]['country_id']);
    }
}
