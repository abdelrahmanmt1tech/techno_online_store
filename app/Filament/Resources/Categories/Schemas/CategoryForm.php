<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.category_details'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('name.ar')
                            ->label(__('dashboard.name_ar'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name.en')
                            ->label(__('dashboard.name_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label(__('dashboard.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        FileUpload::make('image')
                            ->label(__('dashboard.image'))
                            ->image()
                            ->directory('categories')
                            ->columnSpan(3),
                        TextInput::make('order')
                            ->label(__('dashboard.order'))
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
