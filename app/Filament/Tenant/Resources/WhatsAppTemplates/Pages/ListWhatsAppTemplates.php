<?php

namespace App\Filament\Tenant\Resources\WhatsAppTemplates\Pages;

use App\Filament\Shared\WhatsApp\Actions\SyncWhatsAppTemplatesAction;
use App\Filament\Tenant\Resources\WhatsAppTemplates\WhatsAppTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListWhatsAppTemplates extends ListRecords
{
    protected static string $resource = WhatsAppTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SyncWhatsAppTemplatesAction::make(
                fn (): bool => (bool) Auth::user()?->can('whatsapp.manage_templates'),
            ),
            CreateAction::make()
                ->visible(fn () => Auth::user()?->can('whatsapp.manage_templates')),
        ];
    }
}
