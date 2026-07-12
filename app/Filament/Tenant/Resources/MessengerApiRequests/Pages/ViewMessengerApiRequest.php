<?php

namespace App\Filament\Tenant\Resources\MessengerApiRequests\Pages;

use App\Filament\Tenant\Resources\MessengerApiRequests\MessengerApiRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMessengerApiRequest extends ViewRecord
{
    protected static string $resource = MessengerApiRequestResource::class;

    protected string $view = 'filament.shared.messenger.view-api-request';
}
