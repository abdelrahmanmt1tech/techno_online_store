<?php

namespace App\Filament\Tenant\Resources\WhatsAppContacts\Pages;

use App\Filament\Shared\WhatsApp\Actions\SendWhatsAppMessageFilamentAction;
use App\Filament\Tenant\Resources\WhatsAppContacts\WhatsAppContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppContacts extends ListRecords
{
    protected static string $resource = WhatsAppContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendWhatsAppMessageFilamentAction::make(
                name: 'sendToNumber',
                resolvePhone: fn (): string => '',
                includePhoneField: true,
                label: __('dashboard.whatsapp_send_to_number'),
            ),
            CreateAction::make(),
        ];
    }
}
