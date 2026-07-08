<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\TenantPanelProvider;
use App\Providers\TenancyServiceProvider;

return [
    TenantPanelProvider::class,
    AdminPanelProvider::class,
    AppServiceProvider::class,
    TenancyServiceProvider::class,
];
