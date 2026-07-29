<?php

namespace App\Filament\Tenant\Resources\HrJobTitles\Pages;

use App\Filament\Tenant\Resources\HrJobTitles\HrJobTitleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrJobTitles extends ListRecords
{
    protected static string $resource = HrJobTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
