<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps\Schemas;

use App\Filament\Crm\Schemas\OpportunityFollowUpInfolist;
use Filament\Schemas\Schema;

class OpportunityFollowUpInfolistSchema
{
    public static function configure(Schema $schema): Schema
    {
        return OpportunityFollowUpInfolist::configure($schema);
    }
}
