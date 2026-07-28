<?php

namespace App\Filament\Tenant\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('erp.fields.name'))->required()->maxLength(255),
                    TextInput::make('slug')->label(__('commerce.fields.slug'))->required()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('sort_order')->label(__('commerce.fields.sort_order'))->numeric()->default(0),
                    FileUpload::make('logo')
                        ->label(__('commerce.fields.logo'))
                        ->image()
                        ->directory('brands')
                        ->maxSize(2048)
                        ->imagePreviewHeight('80'),
                    Toggle::make('is_active')->label(__('erp.fields.is_active'))->default(true),
                    Textarea::make('description')->label(__('erp.fields.description'))->rows(3)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
