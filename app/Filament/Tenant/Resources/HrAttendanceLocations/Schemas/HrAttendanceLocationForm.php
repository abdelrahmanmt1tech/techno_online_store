<?php

namespace App\Filament\Tenant\Resources\HrAttendanceLocations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrAttendanceLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('hr.fields.name'))->required()->maxLength(255),
                    Select::make('branch_id')
                        ->label(__('hr.fields.branch'))
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Toggle::make('is_active')->label(__('hr.fields.is_active'))->default(true),
                    TextInput::make('latitude')
                        ->label(__('hr.fields.latitude'))
                        ->numeric()
                        ->step(0.0000001)
                        ->required(),
                    TextInput::make('longitude')
                        ->label(__('hr.fields.longitude'))
                        ->numeric()
                        ->step(0.0000001)
                        ->required(),
                    TextInput::make('allowed_radius_meters')
                        ->label(__('hr.fields.allowed_radius_meters'))
                        ->numeric()
                        ->minValue(1)
                        ->default(150)
                        ->required(),
                    TextInput::make('minimum_accuracy_meters')
                        ->label(__('hr.fields.minimum_accuracy_meters'))
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                    Textarea::make('notes')->label(__('hr.fields.notes'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
