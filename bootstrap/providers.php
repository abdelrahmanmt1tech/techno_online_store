<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CrmPanelProvider;
use App\Providers\Filament\TenantPanelProvider;
use App\Providers\TenancyServiceProvider;

return [
    TenantPanelProvider::class,
    CrmPanelProvider::class,
    AdminPanelProvider::class,
    AppServiceProvider::class,
    TenancyServiceProvider::class,
];
