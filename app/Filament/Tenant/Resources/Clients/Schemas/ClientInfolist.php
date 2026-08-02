<?php

namespace App\Filament\Tenant\Resources\Clients\Schemas;

use App\Filament\Crm\Schemas\ClientCrmWorkspaceInfolist;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return ClientCrmWorkspaceInfolist::configure($schema);
    }
}
