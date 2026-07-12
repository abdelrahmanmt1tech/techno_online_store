<?php

namespace App\Filament\Resources\Themes\Schemas;

use App\Models\Category;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ThemeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.theme_details'))
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

                        Textarea::make('description.ar')
                            ->label(__('dashboard.description_ar'))
                            ->rows(3),

                        Textarea::make('description.en')
                            ->label(__('dashboard.description_en'))
                            ->rows(3),

                        TextInput::make('slug')
                            ->label(__('dashboard.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('preview_url')
                            ->label(__('dashboard.preview_url'))
                            ->url()
                            ->maxLength(255),

                        FileUpload::make('image')
                            ->label(__('dashboard.image'))
                            ->image()
                            ->directory('themes')
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.theme_options'))
                    ->columns(3)
                    ->schema([

                        Toggle::make('is_free')
                            ->label(__('dashboard.is_free'))
                            ->default(true)
                            ->live(),

                        TextInput::make('price')
                            ->label(__('dashboard.price'))
                            ->numeric()
                            ->prefix('USD')
                            ->minValue(0)
                            ->visible(fn ($get) => ! $get('is_free')),

                        Toggle::make('featured')
                            ->label(__('dashboard.featured'))
                            ->default(false),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true),

                        Select::make('categories')
                            ->label(__('dashboard.categories'))
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->suffixAction(
                                Action::make('add_category')
                                    ->icon('heroicon-o-plus-circle')
                                    ->schema([
                                        Grid::make()
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
                                            ]),
                                    ])
                                    ->action(function (array $data, Get $get, Set $set) {
                                        $category = Category::create([
                                            'name' => $data['name'],
                                            'slug' => Str::slug(
                                                $data['name']['en'] ?? $data['name']['ar']
                                            ),
                                        ]);

                                        $currentCategories = $get('categories') ?? [];

                                        $set(
                                            'categories',
                                            array_unique([
                                                ...$currentCategories,
                                                $category->id,
                                            ])
                                        );
                                    })
                            ),

                        TextInput::make('order')
                            ->label(__('dashboard.order'))
                            ->numeric()
                            ->default(0),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
