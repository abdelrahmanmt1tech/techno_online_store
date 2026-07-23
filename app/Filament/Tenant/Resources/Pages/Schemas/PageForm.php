<?php

namespace App\Filament\Tenant\Resources\Pages\Schemas;

use App\Filament\Shared\SeoFormOnelanguageSection;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.page_details'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('dashboard.page_title'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', \Illuminate\Support\Str::slug($state)))
                            ->columnSpan(1),

                        TextInput::make('slug')
                            ->label(__('dashboard.page_slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),
                        TextInput::make('sort_order')
                            ->label(__('dashboard.page_sort_order'))
                            ->numeric()
                            ->default(0)
                            ->columnSpan(1),

                        FileUpload::make('image')
                            ->label(__('dashboard.page_image'))
                            ->image()
                            ->directory('pages')
                            ->columnSpanFull(),

                        RichEditor::make('content')
                            ->label(__('dashboard.page_content'))
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label(__('dashboard.page_active'))
                            ->default(true)
                            ->columnSpan(1),

                        Toggle::make('show_in_header')
                            ->label(__('dashboard.show_in_header'))
                            ->default(false)
                            ->columnSpan(1),

                        Toggle::make('show_in_footer')
                            ->label(__('dashboard.show_in_footer'))
                            ->default(false)
                            ->columnSpan(1),

                    ])
                    ->columnSpanFull(),

                SeoFormOnelanguageSection::make()->columnSpanFull(),
            ]);
    }
}
