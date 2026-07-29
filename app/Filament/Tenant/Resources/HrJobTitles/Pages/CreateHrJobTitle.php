<?php

namespace App\Filament\Tenant\Resources\HrJobTitles\Pages;

use App\Filament\Tenant\Resources\HrJobTitles\HrJobTitleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHrJobTitle extends CreateRecord
{
    protected static string $resource = HrJobTitleResource::class;
}
