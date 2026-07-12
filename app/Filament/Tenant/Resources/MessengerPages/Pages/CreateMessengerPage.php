<?php

namespace App\Filament\Tenant\Resources\MessengerPages\Pages;

use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use App\Models\Tenant\MessengerPage;
use Filament\Resources\Pages\CreateRecord;

class CreateMessengerPage extends CreateRecord
{
    protected static string $resource = MessengerPageResource::class;

    protected function afterCreate(): void
    {
        $this->ensureSingleDefault();
    }

    protected function ensureSingleDefault(): void
    {
        if (! $this->record->is_default) {
            return;
        }

        MessengerPage::query()
            ->whereKeyNot($this->record->getKey())
            ->update(['is_default' => false]);
    }
}
