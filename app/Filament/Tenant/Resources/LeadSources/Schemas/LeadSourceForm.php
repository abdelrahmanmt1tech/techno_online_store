<?php

namespace App\Filament\Tenant\Resources\LeadSources\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeadSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name.ar')
                    ->label(__('dashboard.fields.name_ar'))->suffix("ar")
                    ->required(),

                TextInput::make('name.en')
                    ->label(__('dashboard.fields.name_en'))->suffix("en")
                    ->required(),


            ]);
    }
}
