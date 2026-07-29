<?php

namespace App\Filament\Tenant\Resources\HrJobTitles\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrJobTitleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr.sections.details'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label(__('hr.fields.name'))->required()->maxLength(255),
                    Toggle::make('is_active')->label(__('hr.fields.is_active'))->default(true),
                    Textarea::make('description')->label(__('hr.fields.description'))->rows(3)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
