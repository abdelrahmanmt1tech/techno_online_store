<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.blog_tag_details'))
                    ->columns(2)
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
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
