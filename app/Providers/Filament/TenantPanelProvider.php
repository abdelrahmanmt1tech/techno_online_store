<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Tenant\Pages\Dashboard;
use App\Filament\Tenant\Widgets\OrderStatusPie;
use App\Filament\Tenant\Widgets\OrdersTrend;
use App\Filament\Tenant\Widgets\StoreKpis;
use App\Http\Controllers\Tenant\Erp\PurchaseInvoicePrintController;
use App\Http\Controllers\Tenant\Erp\SalesInvoicePrintController;
use App\Http\Controllers\Tenant\Hr\EmployeeAttendanceController;
use App\Http\Controllers\Tenant\Hr\SalarySlipPrintController;
use App\Http\Controllers\Tenant\Pos\PosApiController;
use App\Http\Controllers\Tenant\Pos\PosPageController;
use App\Http\Middleware\EnsureTenantIsInitialized;
use App\Http\Middleware\EnsureTenantModuleActive;
use App\Http\Middleware\TenantAuthenticateSession;
use App\Support\Filament\TenantNavigationBuilder;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('app')
            ->authGuard('tenant')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->profile()
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\Filament\Tenant\Resources')
            ->discoverResources(in: app_path('Filament/Crm/Resources'), for: 'App\Filament\Crm\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\Filament\Tenant\Pages')
            ->discoverPages(in: app_path('Filament/Crm/Pages'), for: 'App\Filament\Crm\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\Filament\Tenant\Widgets')
            ->discoverWidgets(in: app_path('Filament/Crm/Widgets'), for: 'App\Filament\Crm\Widgets')
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return app(TenantNavigationBuilder::class)->build($builder);
            })
            ->assets([
                Css::make('custom-stylesheet', resource_path('css/filament-custom.css')),
                Css::make('whatsapp-ui', resource_path('css/whatsapp-ui.css')),
                Css::make('messaging-health-dashboard', resource_path('css/messaging-health-dashboard.css')),
                Css::make('crm-custom-stylesheet', resource_path('css/crm-custom.css')),
                Css::make('accounting-reports', resource_path('css/accounting-reports.css')),
            ])
            ->widgets([
                StoreKpis::class,
                OrdersTrend::class,
                OrderStatusPie::class,
            ])
            ->persistentMiddleware([
                InitializeTenancyByDomain::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                TenantAuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                EnsureTenantIsInitialized::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authenticatedRoutes(function () {
                Route::middleware([EnsureTenantModuleActive::class.':store,pos'])->group(function () {
                    Route::get(
                        'erp/sales-invoices/{salesInvoice}/print',
                        SalesInvoicePrintController::class,
                    )->name('erp.sales-invoices.print');

                    Route::get(
                        'erp/purchase-invoices/{purchaseInvoice}/print',
                        PurchaseInvoicePrintController::class,
                    )->name('erp.purchase-invoices.print');
                });

                Route::middleware([EnsureTenantModuleActive::class.':pos'])->group(function () {
                    Route::get('pos', PosPageController::class)->name('pos');
                    Route::get('pos/receipt/{sale}', [PosApiController::class, 'receipt'])->name('pos.receipt');

                    Route::prefix('pos/api')->name('pos.api.')->group(function () {
                        Route::get('bootstrap', [PosApiController::class, 'bootstrap'])->name('bootstrap');
                        Route::get('products', [PosApiController::class, 'products'])->name('products');
                        Route::get('barcode', [PosApiController::class, 'barcode'])->name('barcode');
                        Route::get('customers', [PosApiController::class, 'customers'])->name('customers');
                        Route::post('customers', [PosApiController::class, 'storeCustomer'])->name('customers.store');
                        Route::post('session/open', [PosApiController::class, 'openSession'])->name('session.open');
                        Route::get('session/status', [PosApiController::class, 'sessionStatus'])->name('session.status');
                        Route::post('session/close', [PosApiController::class, 'closeSession'])->name('session.close');
                        Route::get('session/summary', [PosApiController::class, 'shiftSummary'])->name('session.summary');
                        Route::post('cash-in', [PosApiController::class, 'cashIn'])->name('cash-in');
                        Route::post('cash-out', [PosApiController::class, 'cashOut'])->name('cash-out');
                        Route::post('checkout', [PosApiController::class, 'checkout'])->name('checkout');
                        Route::post('suspend', [PosApiController::class, 'suspend'])->name('suspend');
                        Route::get('suspended', [PosApiController::class, 'suspended'])->name('suspended');
                        Route::post('suspended/{sale}/resume', [PosApiController::class, 'resume'])->name('suspended.resume');
                        Route::post('suspended/{sale}/cancel', [PosApiController::class, 'cancelSuspended'])->name('suspended.cancel');
                    });
                });

                Route::middleware([EnsureTenantModuleActive::class.':hr'])->group(function () {
                    Route::get('hr/attendance', [EmployeeAttendanceController::class, 'page'])->name('hr.attendance');
                    Route::get('hr/attendance/status', [EmployeeAttendanceController::class, 'status'])->name('hr.attendance.status');
                    Route::get('hr/attendance/distance', [EmployeeAttendanceController::class, 'distance'])->name('hr.attendance.distance');
                    Route::post('hr/attendance/check-in', [EmployeeAttendanceController::class, 'checkIn'])->name('hr.attendance.check-in');
                    Route::post('hr/attendance/check-out', [EmployeeAttendanceController::class, 'checkOut'])->name('hr.attendance.check-out');
                    Route::get(
                        'hr/payroll-employees/{payrollEmployee}/slip',
                        SalarySlipPrintController::class,
                    )->name('hr.payroll.slip');
                });
            })

            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem')


            ;
    }
}
