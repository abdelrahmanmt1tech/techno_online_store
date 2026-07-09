<?php

namespace App\Filament\Tenant\Resources\WhatsAppTemplates\Pages;

use App\Filament\Tenant\Resources\WhatsAppTemplates\WhatsAppTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditWhatsAppTemplate extends EditRecord
{
    protected static string $resource = WhatsAppTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => Auth::user()?->can('whatsapp.manage_templates')),
        ];
    }
}
