<?php

namespace App\Filament\Tenant\Resources\WhatsAppNumbers\Pages;

use App\Filament\Tenant\Resources\WhatsAppNumbers\WhatsAppNumberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListWhatsAppNumbers extends ListRecords
{
    protected static string $resource = WhatsAppNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => Auth::user()?->can('whatsapp.manage_numbers')),
        ];
    }
}
