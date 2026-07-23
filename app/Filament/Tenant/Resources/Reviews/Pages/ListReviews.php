<?php

namespace App\Filament\Tenant\Resources\Reviews\Pages;

use App\Filament\Tenant\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
}
