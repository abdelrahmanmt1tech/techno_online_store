<?php

namespace App\Filament\Tenant\Resources\Customers\Pages;

use App\Filament\Tenant\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;
}
