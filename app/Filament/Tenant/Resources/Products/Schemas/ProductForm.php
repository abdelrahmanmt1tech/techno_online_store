<?php

namespace App\Filament\Tenant\Resources\Products\Schemas;

use App\Models\Tenant\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('product_form')
                    ->contained(false)
                    ->tabs([
                        Tab::make(__('dashboard.general'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                self::generalSection(),
                                self::pricingSection(),
                            ]),

                        Tab::make(__('dashboard.inventory'))
                            ->icon('heroicon-o-cube')
                            ->schema([
                                self::inventorySection(),
                            ]),

                        Tab::make(__('dashboard.product_type'))
                            ->icon('heroicon-o-tag')
                            ->schema([
                                self::productTypeSection(),
                            ]),

                        Tab::make(__('dashboard.gallery'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                self::gallerySection(),
                            ]),

                        Tab::make(__('dashboard.attributes'))
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                self::attributesSection(),
                            ]),

                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function generalSection(): Section
    {
        return Section::make(__('dashboard.product_information'))
            ->columns(3)
            ->schema([
                TextInput::make('name')
                    ->label(__('dashboard.name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state)))
                    ->columnSpan(1),

                TextInput::make('slug')
                    ->label(__('dashboard.slug'))
                    ->required()
                    ->unique(Product::class, 'slug', ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('sku')
                    ->label(__('dashboard.sku'))
                    ->maxLength(255)
                    ->helperText(__('dashboard.sku_helper')),

                Select::make('categories')
                    ->label(__('dashboard.categories'))
                    ->relationship(
                        name: 'categories',
                        titleAttribute: 'name'
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->columnSpan(2),

                TextInput::make('order')
                    ->label(__('dashboard.order'))
                    ->numeric()
                    ->default(0)
                    ->helperText(__('dashboard.order_helper')),

                RichEditor::make('description')
                    ->label(__('dashboard.description'))
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('products/descriptions'),

                Toggle::make('is_active')
                    ->label(__('dashboard.active'))
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }

    private static function pricingSection(): Section
    {
        return Section::make(__('dashboard.pricing'))
            ->columns(3)
            ->schema([
                TextInput::make('price')
                    ->label(__('dashboard.price'))
                    ->required()
                    ->numeric()
                    ->prefix('SAR')
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => self::updateProfitMargin($set, $get)),

                TextInput::make('sale_price')
                    ->label(__('dashboard.sale_price'))
                    ->numeric()
                    ->prefix('SAR')
                    ->minValue(0)
                    ->nullable()
                    ->helperText(__('dashboard.sale_price_helper'))
                    ->live()
                    ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => self::updateProfitMargin($set, $get)),

                TextInput::make('expense')
                    ->label(__('dashboard.expense'))
                    ->numeric()
                    ->prefix('SAR')
                    ->minValue(0)
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => self::updateProfitMargin($set, $get)),

                TextEntry::make('profit_margin')
                    ->label(__('dashboard.profit_margin'))
                    ->state(fn (Get $get) => new HtmlString(self::calculateProfitMargin($get)))
                    ->html()
                    ->columnSpanFull(),
            ]);
    }

    private static function inventorySection(): Section
    {
        return Section::make(__('dashboard.stock_management'))
            ->columns(2)
            ->schema([
                TextInput::make('quantity')
                    ->label(__('dashboard.quantity'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->live()
                    ->visible(fn (Get $get) => (bool) $get('track_stock')),

                Toggle::make('track_stock')
                    ->label(__('dashboard.track_stock'))
                    ->default(false)
                    ->live()
                    ->helperText(__('dashboard.track_stock_helper'))
                    ->afterStateUpdated(fn (Set $set, $state) => $state ?: $set('disable_orders_for_no_stock', false)),

                Toggle::make('disable_orders_for_no_stock')
                    ->label(__('dashboard.disable_orders_for_no_stock'))
                    ->default(false)
                    ->visible(fn (Get $get) => (bool) $get('track_stock'))
                    ->helperText(__('dashboard.disable_orders_for_no_stock_helper')),
            ]);
    }

    private static function productTypeSection(): Section
    {
        return Section::make(__('dashboard.product_type'))
            ->schema([
                Radio::make('type')
                    ->label(__('dashboard.type'))
                    ->options([
                        'physical' => __('dashboard.physical'),
                        'digital' => __('dashboard.digital'),
                    ])
                    ->default('physical')
                    ->live()
                    ->inline(),

                Grid::make()
                    ->columns(2)
                    ->visible(fn (Get $get) => $get('type') === 'digital')
                    ->schema([
                        Select::make('digital_delivery_type')
                            ->label(__('dashboard.digital_delivery_type'))
                            ->options([
                                'link' => __('dashboard.download_link'),
                                'codes' => __('dashboard.product_codes'),
                            ])
                            ->default('link')
                            ->live()
                            ->native(false)
                            ->reactive()
                            ->dehydrated(false),

                        TextInput::make('link_if_type_digital')
                            ->label(__('dashboard.download_link'))
                            ->url()
                            ->visible(fn (Get $get) => $get('digital_delivery_type') === 'link')
                            ->helperText(__('dashboard.download_link_helper'))
                            ->columnSpanFull(),

                        Repeater::make('codes')
                            ->label(__('dashboard.product_codes'))
                            ->relationship('codes')
                            ->visible(fn (Get $get) => $get('digital_delivery_type') === 'codes')
                            ->simple(
                                Textarea::make('code')
                                    ->label(__('dashboard.code'))
                                    ->required()
                                    ->rows(2),
                            )
                            ->addActionLabel(__('dashboard.add_code'))
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->hintAction(
                                Action::make('bulk_paste')
                                    ->label(__('dashboard.bulk_paste'))
                                    ->icon('heroicon-o-clipboard-document-list')
                                    ->form([
                                        Textarea::make('codes')
                                            ->label(__('dashboard.bulk_paste'))
                                            ->rows(10)
                                            ->helperText(__('dashboard.bulk_paste_helper')),
                                    ])
                                    ->action(function (array $data, Set $set, Get $get): void {
                                        $codes = collect(explode("\n", $data['codes']))
                                            ->map(fn ($line) => trim($line))
                                            ->filter()
                                            ->values()
                                            ->toArray();

                                        $existing = $get('codes') ?? [];
                                        foreach ($codes as $code) {
                                            $existing[] = ['code' => $code];
                                        }
                                        $set('codes', $existing);
                                    }),
                            ),
                    ]),
            ]);
    }

    private static function gallerySection(): Section
    {
        return Section::make(__('dashboard.product_gallery'))
            ->schema([
                FileUpload::make('gallery')
                    ->label(__('dashboard.images'))
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->openable()
                    ->panelLayout('grid')
                    ->imagePreviewHeight('150')
                    ->minFiles(0)
                    ->maxFiles(10)
                    ->maxSize(2048)
                    ->disk('public')
                    ->directory('products/gallery')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->helperText(__('dashboard.gallery_helper'))
                    ->saveRelationshipsUsing(function ($component, $state, $record) {
                        $record->media()->delete();

                        if (blank($state)) {
                            return;
                        }

                        foreach ($state as $order => $file) {
                            $record->media()->create([
                                'file' => $file,
                                'type' => 'image',
                            ]);
                        }
                    })
                    ->formatStateUsing(function ($record): array {
                        if (! $record) {
                            return [];
                        }

                        return $record->media->pluck('file')->toArray();
                    }),
            ]);
    }

    private static function attributesSection(): Section
    {
        return Section::make(__('dashboard.product_attributes'))
            ->schema([
                Repeater::make('attributes')
                    ->label(__('dashboard.attributes'))
                    ->relationship('attributes')
                    ->reorderable(true)
                    ->collapsible()
                    ->collapsed(false)
                    ->itemLabel(fn (array $state) => $state['name'] ?? __('dashboard.new_attribute'))
                    ->saveRelationshipsUsing(function ($component, ?array $state, $record): void {
                        if (! $record) {
                            return;
                        }

                        $attributeIds = [];

                        foreach ($state ?? [] as $attributeData) {
                            $values = $attributeData['values'] ?? [];
                            unset($attributeData['values']);

                            $data = collect($attributeData)
                                ->except(['id'])
                                ->reject(fn ($value) => is_array($value))
                                ->toArray();

                            $attribute = $record->attributes()->updateOrCreate(
                                ['id' => $attributeData['id'] ?? null],
                                $data,
                            );

                            $attributeIds[] = $attribute->id;

                            $valueIds = [];

                            foreach ($values as $valueData) {
                                $value = $attribute->values()->updateOrCreate(
                                    ['id' => $valueData['id'] ?? null],
                                    array_merge(
                                        collect($valueData)
                                            ->except(['id'])
                                            ->reject(fn ($value) => is_array($value))
                                            ->toArray(),
                                        ['product_id' => $record->id],
                                    ),
                                );

                                $valueIds[] = $value->id;
                            }

                            $attribute->values()
                                ->whereNotIn('id', $valueIds)
                                ->delete();
                        }

                        $record->attributes()
                            ->whereNotIn('id', $attributeIds)
                            ->each(fn ($attr) => $attr->delete());
                    })
                    ->schema([
                        Grid::make()
                            ->columns(4)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('dashboard.attribute_name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->columnSpan(2),

                                Select::make('type')
                                    ->label(__('dashboard.attribute_type'))
                                    ->options([
                                        'color' => __('dashboard.color'),
                                        'button' => __('dashboard.button'),
                                        'image' => __('dashboard.image'),
                                        'user_text' => __('dashboard.user_text'),
                                        'user_image' => __('dashboard.user_image'),
                                        'dropdown' => __('dashboard.dropdown'),
                                    ])
                                    ->required()
                                    ->live()
                                    ->native(false),

                                Toggle::make('is_available')
                                    ->label(__('dashboard.available'))
                                    ->default(true),
                            ]),

                        Grid::make()
                            ->columns(2)
                            ->schema([
                                ColorPicker::make('color_code')
                                    ->label(__('dashboard.color_code'))
                                    ->visible(fn (Get $get) => $get('type') === 'color'),

                                FileUpload::make('image')
                                    ->label(__('dashboard.image'))
                                    ->image()
                                    ->directory('products/attributes')
                                    ->visible(fn (Get $get) => $get('type') === 'image')
                                    ->maxSize(1024),
                            ]),

                        Repeater::make('values')
                            ->label(__('dashboard.attribute_values'))
                            ->relationship('values')
                            ->reorderable(true)
                            ->collapsible()
                            ->defaultItems(0)
                            ->schema([
                                Hidden::make('product_id'),

                                Grid::make()
                                    ->columns(6)
                                    ->schema([
                                        TextInput::make('attribute_value')
                                            ->label(__('dashboard.value'))
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(1),

                                        FileUpload::make('image')
                                            ->label(__('dashboard.image'))
                                            ->image()
                                            ->directory('products/attribute-values')
                                            ->maxSize(1024)
                                            ->visible(fn (Get $get) => $get('../../type') === 'image'),

                                        TextInput::make('sku')
                                            ->label(__('dashboard.sku'))
                                            ->maxLength(255)
                                            ->columnSpan(1),

                                        TextInput::make('price')
                                            ->label(__('dashboard.price'))
                                            ->numeric()
                                            ->prefix('SAR')
                                            ->default(0)
                                            ->minValue(0)
                                            ->columnSpan(1),

                                        TextInput::make('sale_price')
                                            ->label(__('dashboard.sale_price'))
                                            ->numeric()
                                            ->prefix('SAR')
                                            ->minValue(0)
                                            ->nullable()
                                            ->columnSpan(1),

                                        TextInput::make('expense')
                                            ->label(__('dashboard.expense'))
                                            ->numeric()
                                            ->prefix('SAR')
                                            ->minValue(0)
                                            ->nullable()
                                            ->columnSpan(1),

                                        TextInput::make('quantity')
                                            ->label(__('dashboard.quantity'))
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->columnSpan(1),
                                    ]),
                            ]),
                    ])
                    ->defaultItems(0),
            ]);
    }

    private static function generateCombinations(array $attributes): array
    {
        $valuesPerAttribute = [];
        foreach ($attributes as $attr) {
            $values = collect($attr['values'] ?? [])
                ->pluck('attribute_value')
                ->filter()
                ->values()
                ->toArray();

            if (! empty($values)) {
                $valuesPerAttribute[] = $values;
            }
        }

        if (empty($valuesPerAttribute)) {
            return [];
        }

        $combinations = [[]];
        foreach ($valuesPerAttribute as $values) {
            $new = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $new[] = array_merge($combination, [$value]);
                }
            }
            $combinations = $new;
        }

        return array_map(fn ($combo) => ['values' => $combo], $combinations);
    }

    // private static function buildCategoryOptions($category, int $level = 0): array
    // {
    //     $options = [];

    //     $prefix = $level > 0
    //         ? str_repeat("\u{3000}\u{3000}", $level - 1) . "\u{21B3} "
    //         : '';

    //     $options[$category->id] = $prefix . $category->name;

    //     if ($category->children->isNotEmpty()) {
    //         foreach ($category->children as $child) {
    //             $options += self::buildCategoryOptions($child, $level + 1);
    //         }
    //     }

    //     return $options;
    // }

    private static function updateProfitMargin(Set $set, Get $get): void
    {
        $set('profit_margin', self::calculateProfitMargin($get));
    }

    private static function calculateProfitMargin(Get $get): string
    {
        $price = (float) ($get('sale_price') ?: $get('price') ?? 0);
        $expense = (float) ($get('expense') ?? 0);
        $margin = $price - $expense;

        if ($margin <= 0) {
            return '<span class="text-danger-600 dark:text-danger-400">'
                .__('dashboard.no_profit').'</span>';
        }

        $percentage = $price > 0 ? round(($margin / $price) * 100, 1) : 0;

        return '<span class="text-success-600 dark:text-success-400">'
            .number_format($margin, 2).' SAR ('.$percentage.'%)</span>';
    }
}
