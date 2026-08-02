<?php

namespace App\Filament\Crm\Resources\FollowUpStatuses\Pages;

use App\Filament\Crm\Resources\FollowUpStatuses\FollowUpStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFollowUpStatuses extends ManageRecords
{
    protected static string $resource = FollowUpStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
