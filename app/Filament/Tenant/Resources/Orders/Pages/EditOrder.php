<?php

namespace App\Filament\Tenant\Resources\Orders\Pages;

use App\Filament\Tenant\Resources\Orders\OrderResource;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;
}
