<?php

namespace App\Filament\Tenant\Resources\LeadSources\Pages;

use App\Filament\Tenant\Resources\LeadSources\LeadSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeadSource extends CreateRecord
{
    protected static string $resource = LeadSourceResource::class;
}
