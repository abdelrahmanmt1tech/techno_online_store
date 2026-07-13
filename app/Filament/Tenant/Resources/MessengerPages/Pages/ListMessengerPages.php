<?php

namespace App\Filament\Tenant\Resources\MessengerPages\Pages;

use App\Filament\Tenant\Pages\ConnectMessengerPage;
use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListMessengerPages extends ListRecords
{
    protected static string $resource = MessengerPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connectMessenger')
                ->label(__('dashboard.messenger_connect'))
                ->url(fn (): string => ConnectMessengerPage::getUrl())
                ->visible(fn () => Auth::user()?->can('messenger.manage_pages') || config('app.bypass_permissions')),
            CreateAction::make()
                ->label(__('dashboard.messenger_connect_manual_cta'))
                ->visible(fn () => Auth::user()?->can('messenger.manage_pages') || config('app.bypass_permissions')),
        ];
    }
}
