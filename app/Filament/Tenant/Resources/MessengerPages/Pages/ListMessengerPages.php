<?php

namespace App\Filament\Tenant\Resources\MessengerPages\Pages;

use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use App\Models\Tenant\MessengerPage;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListMessengerPages extends ListRecords
{
    protected static string $resource = MessengerPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => Auth::user()?->can('messenger.manage_pages') || config('app.bypass_permissions')),
        ];
    }
}
