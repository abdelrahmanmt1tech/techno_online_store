<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps\Schemas;

use App\Filament\Crm\Schemas\OpportunityFollowUpFormSchema;
use Filament\Schemas\Schema;

class OpportunityFollowUpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(OpportunityFollowUpFormSchema::configure(includeOpportunity: true));
    }
}
