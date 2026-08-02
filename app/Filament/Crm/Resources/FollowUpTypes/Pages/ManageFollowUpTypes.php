<?php

namespace App\Filament\Crm\Resources\FollowUpTypes\Pages;

use App\Filament\Crm\Resources\FollowUpTypes\FollowUpTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFollowUpTypes extends ManageRecords
{
    protected static string $resource = FollowUpTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
