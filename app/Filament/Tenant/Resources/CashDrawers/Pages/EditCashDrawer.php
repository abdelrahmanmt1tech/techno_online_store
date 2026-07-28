<?php

namespace App\Filament\Tenant\Resources\CashDrawers\Pages;

use App\Filament\Tenant\Resources\CashDrawers\CashDrawerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCashDrawer extends EditRecord
{
    protected static string $resource = CashDrawerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
