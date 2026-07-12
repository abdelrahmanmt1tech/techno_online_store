<?php

namespace App\Filament\Tenant\Resources\MessengerPages\Pages;

use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use App\Models\Tenant\MessengerPage;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditMessengerPage extends EditRecord
{
    protected static string $resource = MessengerPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => Auth::user()?->can('messenger.manage_pages') || config('app.bypass_permissions')),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['page_access_token'] ?? null)) {
            unset($data['page_access_token']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->is_default) {
            return;
        }

        MessengerPage::query()
            ->whereKeyNot($this->record->getKey())
            ->update(['is_default' => false]);
    }
}
