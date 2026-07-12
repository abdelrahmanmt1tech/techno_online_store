<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Resources\Tags\TagResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;


       protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
