<?php

namespace App\Filament\Tenant\Resources\WhatsAppContacts\Pages;

use App\Filament\Tenant\Resources\WhatsAppContacts\WhatsAppContactResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppContact extends EditRecord
{
    protected static string $resource = WhatsAppContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
