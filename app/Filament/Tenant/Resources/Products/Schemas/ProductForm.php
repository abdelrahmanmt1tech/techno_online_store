<?php

namespace App\Filament\Tenant\Resources\Products\Schemas;

use App\Models\Tenant\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
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

                        Tab::make(__('dashboard.variants'))
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                self::variationsSection(),
                                self::variantsSection(),
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

                Textarea::make('description')
                    ->label(__('dashboard.description'))
                    ->rows(5)
                    ->columnSpanFull(),

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
                                    ->schema([
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

    private static function variationsSection(): Section
    {
        return Section::make(__('dashboard.product_variations'))
            ->description(__('dashboard.product_variations_description'))
            ->schema([
                Repeater::make('variations')
                    ->label(__('dashboard.variants'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('dashboard.attribute_name'))
                                    ->required()
                                    ->maxLength(255),

                                Select::make('type')
                                    ->label(__('dashboard.attribute_type'))
                                    ->options([
                                        'color' => __('dashboard.color'),
                                        'button' => __('dashboard.button'),
                                        'dropdown' => __('dashboard.dropdown'),
                                        'user_text' => __('dashboard.user_text'),
                                        'user_image' => __('dashboard.user_image'),
                                        'image' => __('dashboard.image'),
                                    ])
                                    ->default('button')
                                    ->required()
                                    ->native(false),

                                TextInput::make('sort_order')
                                    ->label(__('dashboard.order'))
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Repeater::make('options')
                            ->label(__('dashboard.attribute_values'))
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('value')
                                            ->label(__('dashboard.value'))
                                            ->required()
                                            ->maxLength(255),

                                        ColorPicker::make('color_code')
                                            ->label(__('dashboard.color_code'))
                                            ->visible(fn (Get $get) => $get('../../type') === 'color'),

                                        TextInput::make('order')
                                            ->label(__('dashboard.order'))
                                            ->numeric()
                                            ->default(0),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel(__('dashboard.add_value'))
                            ->reorderable(false),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel(__('dashboard.add_variation'))
                    ->reorderable(false)
                    ->collapsible()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, ?array $state) {
                        self::syncVariants($set, $get);
                    }),
            ]);
    }

    private static function variantsSection(): Section
    {
        return Section::make(__('dashboard.variants'))
            ->description(__('dashboard.variants_description'))
            ->schema([
                Repeater::make('variants')
                    ->label(__('dashboard.variants_matrix'))
                    ->schema([
                        Grid::make(7)
                            ->schema([
                                Hidden::make('combination')
                                    ->dehydrated(),

                                TextInput::make('combination_label')
                                    ->label(__('dashboard.variant_options'))
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('price')
                                    ->label(__('dashboard.price'))
                                    ->required()
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0),

                                TextInput::make('sale_price')
                                    ->label(__('dashboard.sale_price'))
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0)
                                    ->nullable(),

                                TextInput::make('expense')
                                    ->label(__('dashboard.expense'))
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0)
                                    ->nullable(),

                                TextInput::make('quantity')
                                    ->label(__('dashboard.quantity'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('sku')
                                    ->label(__('dashboard.sku'))
                                    ->maxLength(255),
                                FileUpload::make('image')
                                    ->label(__('dashboard.image'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('products/variants')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                                Toggle::make('is_active')
                                    ->label(__('dashboard.active'))
                                    ->default(true),

                            ]),
                    ])
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->live(),
            ]);
    }

    private static function syncVariants(Set $set, Get $get): void
    {
        $variations = collect($get('variations') ?? [])
            ->filter(fn ($v) => ! empty($v['name']) && ! empty($v['options']))
            ->values();

        if ($variations->isEmpty()) {
            $set('variants', []);

            return;
        }

        $optionArrays = $variations->mapWithKeys(fn ($v) => [
            $v['name'] => collect($v['options'] ?? [])
                ->filter(fn ($o) => ! empty($o['value']))
                ->pluck('value', 'value')
                ->toArray(),
        ])->toArray();

        $combinations = self::cartesian($optionArrays);

        $existingVariants = collect($get('variants') ?? [])
            ->keyBy(fn ($v) => $v['combination_label'] ?? '');

        $productPrice = (float) ($get('price') ?? 0);
        $productSalePrice = $get('sale_price') ? (float) $get('sale_price') : null;
        $productExpense = $get('expense') ? (float) $get('expense') : null;

        $newVariants = [];

        foreach ($combinations as $combo) {
            $sorted = collect($combo)->sort()->values()->toArray();
            $label = implode(' - ', $sorted);

            if ($existingVariants->has($label)) {
                $existing = $existingVariants->get($label);
                $existing['combination_label'] = $label;
                $existing['combination'] = $combo;
                $newVariants[] = $existing;
            } else {
                $newVariants[] = [
                    'combination_label' => $label,
                    'combination' => $combo,
                    'price' => $productPrice,
                    'sale_price' => $productSalePrice,
                    'expense' => $productExpense,
                    'quantity' => 0,
                    'sku' => null,
                    'image' => null,
                    'is_active' => true,
                ];
            }
        }

        $set('variants', $newVariants);
    }

    private static function cartesian(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $key => $values) {
            $newResult = [];

            foreach ($result as $existing) {
                foreach ($values as $value) {
                    $newResult[] = array_merge($existing, [$key => $value]);
                }
            }

            $result = $newResult;
        }

        return $result;
    }

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
