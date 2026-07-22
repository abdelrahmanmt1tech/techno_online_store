<?php

namespace App\Filament\Tenant\Resources\UnitsOfMeasure\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitOfMeasureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('erp.fields.name'))->required()->maxLength(255),
                    TextInput::make('code')->label(__('erp.fields.code'))->maxLength(50),
                    TextInput::make('symbol')->label(__('erp.fields.symbol'))->maxLength(20),
                    Toggle::make('allows_decimal')->label(__('erp.fields.allows_decimal'))->default(true),
                    TextInput::make('precision')->label(__('erp.fields.precision'))->numeric()->default(2)->minValue(0)->maxValue(6),
                    Toggle::make('is_active')->label(__('erp.fields.is_active'))->default(true),
                ])
                ->columnSpanFull(),
        ]);
    }
}
