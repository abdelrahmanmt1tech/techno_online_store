<?php

namespace App\Filament\Tenant\Resources\WhatsAppNumbers\Pages;

use App\Filament\Tenant\Pages\ConnectWhatsAppPage;
use App\Filament\Tenant\Resources\WhatsAppNumbers\WhatsAppNumberResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ListWhatsAppNumbers extends ListRecords
{
    protected static string $resource = WhatsAppNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connectWhatsApp')
                ->label(__('dashboard.whatsapp_connect'))
                ->icon(Heroicon::Link)
                ->url(ConnectWhatsAppPage::getUrl())
                ->visible(fn () => Auth::user()?->can('whatsapp.manage_numbers') || config('app.bypass_permissions')),
            CreateAction::make()
                ->label(__('dashboard.whatsapp_connect_manual_cta'))
                ->visible(fn () => Auth::user()?->can('whatsapp.manage_numbers') || config('app.bypass_permissions')),
        ];
    }
}
