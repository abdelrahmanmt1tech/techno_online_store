<?php

namespace App\Filament\Tenant\Resources\HrJobTitles\Pages;

use App\Filament\Tenant\Resources\HrJobTitles\HrJobTitleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHrJobTitle extends EditRecord
{
    protected static string $resource = HrJobTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
