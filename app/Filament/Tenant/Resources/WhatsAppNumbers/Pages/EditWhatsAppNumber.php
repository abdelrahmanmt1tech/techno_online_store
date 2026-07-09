<?php

namespace App\Filament\Tenant\Resources\WhatsAppNumbers\Pages;

use App\Filament\Tenant\Resources\WhatsAppNumbers\WhatsAppNumberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditWhatsAppNumber extends EditRecord
{
    protected static string $resource = WhatsAppNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => Auth::user()?->can('whatsapp.manage_numbers')),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['access_token'] ?? null)) {
            unset($data['access_token']);
        }

        return $data;
    }
}
