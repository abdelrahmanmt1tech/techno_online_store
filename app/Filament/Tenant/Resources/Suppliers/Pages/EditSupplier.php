<?php

namespace App\Filament\Tenant\Resources\Suppliers\Pages;

use App\Filament\Tenant\Resources\Suppliers\SupplierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => Auth::user()->can('erp.suppliers.manage')),
        ];
    }
}
