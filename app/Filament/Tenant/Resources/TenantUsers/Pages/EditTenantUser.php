<?php

namespace App\Filament\Tenant\Resources\TenantUsers\Pages;

use App\Filament\Tenant\Resources\TenantUsers\TenantUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTenantUser extends EditRecord
{
    protected static string $resource = TenantUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn ($record) => $record->id !== 1 && Auth::user()->can('tenant-users.destroy')),
        ];
    }
}
