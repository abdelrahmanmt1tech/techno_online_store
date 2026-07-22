<?php

namespace App\Filament\Tenant\Resources\Categories\Schemas;

use App\Filament\Shared\SeoFormOnelanguageSection;
use App\Models\Tenant\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.category_details'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('dashboard.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->label(__('dashboard.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('parent_id')
                            ->label(__('dashboard.parent_category'))
                            ->options(fn () => self::getCategoryOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable(),

                        FileUpload::make('image')
                            ->label(__('dashboard.image'))
                            ->image()
                            ->directory('categories')
                            ->imageEditor()
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label(__('dashboard.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('show_in_header')
                            ->label(__('dashboard.show_in_header')),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active')),

                        Select::make('products')
                            ->label(__('dashboard.products'))
                            ->relationship(
                                name: 'products',
                                titleAttribute: 'name'
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->columnSpanFull(),

                    ])
                    ->columnSpanFull(),

                SeoFormOnelanguageSection::make()->columnSpanFull(),
            ]);
    }

    private static function getCategoryOptions(): array
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->get();

        return self::buildCategoryTree($categories);
    }

    private static function buildCategoryTree($categories, int $level = 0): array
    {
        $options = [];

        foreach ($categories as $category) {
            $prefix = '';

            if ($level > 0) {
                $indent = str_repeat("\u{3000}\u{3000}", $level - 1);
                $prefix = $indent."\u{21B3} ";
            }

            $options[$category->id] = $prefix.$category->name;

            if ($category->children->isNotEmpty()) {
                $options += self::buildCategoryTree(
                    $category->children,
                    $level + 1
                );
            }
        }

        return $options;
    }
}
