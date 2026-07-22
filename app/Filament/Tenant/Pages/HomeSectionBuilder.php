<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Tenant\Category;
use App\Models\Tenant\HomeSection;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class HomeSectionBuilder extends Page
{
    protected static ?int $navigationSort = 70;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.home_section_builder');
    }

    public function getTitle(): string
    {
        return __('dashboard.home_section_builder');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-home';
    }

    public function mount(): void
    {
        $this->loadSections();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedSchema::make('form'),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('dashboard.home_sections'))
                        ->icon(Heroicon::Bars3)
                        ->schema([
                            Repeater::make('sections')
                                ->label('')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label(__('dashboard.section_active'))
                                        ->default(true)
                                        ->columnSpan(1),
                                    Grid::make(3)->schema([
                                        Select::make('type')
                                            ->label(__('dashboard.section_type'))
                                            ->options([
                                                'hero' => __('dashboard.section_type_hero'),
                                                'categories' => __('dashboard.section_type_categories'),
                                                'new_arrivals' => __('dashboard.section_type_new_arrivals'),
                                                'best_sellers' => __('dashboard.section_type_best_sellers'),
                                                'deals' => __('dashboard.section_type_deals'),
                                                'testimonials' => __('dashboard.section_type_testimonials'),
                                            ])
                                            ->required()
                                            ->live()
                                            ->columnSpan(1),

                                        TextInput::make('sort_order')
                                            ->label(__('dashboard.section_sort_order'))
                                            ->numeric()
                                            ->default(0)
                                            ->hidden()
                                            ->columnSpan(1),
                                    ]),

                                    ...$this->heroFields(),
                                    ...$this->categoriesFields(),
                                    ...$this->newArrivalsFields(),
                                    ...$this->bestSellersFields(),
                                    ...$this->dealsFields(),
                                    ...$this->testimonialsFields(),
                                ])
                                ->columns(1)
                                ->defaultItems(0)
                                ->addActionLabel(__('dashboard.add_section'))
                                ->cloneable()
                                ->collapsible()
                                ->reorderable('sort_order')
                                ->reorderableWithButtons()
                                ->itemLabel(fn(array $state): ?string => $state['type']
                                    ? __('dashboard.section_type_' . $state['type'])
                                    : null),
                        ])
                        ->columnSpanFull(),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->submit('save')
                                ->label(__('dashboard.save'))
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    private function heroFields(): array
    {
        return [
            Section::make(__('dashboard.section_type_hero'))
                ->icon(Heroicon::Photo)
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label(__('dashboard.hero_title')),
                    TextInput::make('subtitle')
                        ->label(__('dashboard.hero_subtitle')),
                    TextInput::make('button_text')
                        ->label(__('dashboard.hero_button_text')),
                    TextInput::make('button_url')
                        ->label(__('dashboard.hero_button_url')),
                    FileUpload::make('image')
                        ->label(__('dashboard.hero_background_image'))
                        ->image()
                        ->directory('home/hero')
                        ->optimize('webp'),
                ])
                ->visible(fn($get) => $get('type') === 'hero')
                ->columnSpanFull(),
        ];
    }

    private function categoriesFields(): array
    {
        return [
            Section::make(__('dashboard.section_type_categories'))
                ->icon(Heroicon::Squares2x2)
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label(__('dashboard.categories_title')),
                    CheckboxList::make('category_ids')
                        ->label(__('dashboard.categories_select'))
                        ->options(fn() => Category::where('is_active', true)->pluck('name', 'id'))
                        ->columns(3)
                        ->searchable()
                        ->columnSpanFull(),
                ])
                ->visible(fn($get) => $get('type') === 'categories')
                ->columnSpanFull(),
        ];
    }

    private function newArrivalsFields(): array
    {
        return [
            Section::make(__('dashboard.section_type_new_arrivals'))
                ->icon(Heroicon::Sparkles)
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label(__('dashboard.new_arrivals_title')),
                    TextInput::make('subtitle')
                        ->label(__('dashboard.new_arrivals_subtitle')),
                    TextInput::make('products_count')
                        ->label(__('dashboard.new_arrivals_products_count'))
                        ->numeric()
                        ->default(8)
                        ->minValue(1)
                        ->maxValue(50),
                ])
                ->visible(fn($get) => $get('type') === 'new_arrivals')
                ->columnSpanFull(),
        ];
    }

    private function bestSellersFields(): array
    {
        return [
            Section::make(__('dashboard.section_type_best_sellers'))
                ->icon(Heroicon::Trophy)
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label(__('dashboard.best_sellers_title')),
                    TextInput::make('subtitle')
                        ->label(__('dashboard.best_sellers_subtitle')),
                    TextInput::make('products_count')
                        ->label(__('dashboard.best_sellers_products_count'))
                        ->numeric()
                        ->default(8)
                        ->minValue(1)
                        ->maxValue(50),
                ])
                ->visible(fn($get) => $get('type') === 'best_sellers')
                ->columnSpanFull(),
        ];
    }

    private function dealsFields(): array
    {
        return [
            Section::make(__('dashboard.section_type_deals'))
                ->icon(Heroicon::Fire)
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label(__('dashboard.deals_title')),
                    TextInput::make('subtitle')
                        ->label(__('dashboard.deals_subtitle')),
                    TextInput::make('products_count')
                        ->label(__('dashboard.deals_products_count'))
                        ->numeric()
                        ->default(8)
                        ->minValue(1)
                        ->maxValue(50),
                ])
                ->visible(fn($get) => $get('type') === 'deals')
                ->columnSpanFull(),
        ];
    }

    private function testimonialsFields(): array
    {
        return [
            Section::make(__('dashboard.section_type_testimonials'))
                ->icon(Heroicon::ChatBubbleLeftRight)
                ->schema([
                    TextInput::make('title')
                        ->label(__('dashboard.testimonials_title')),
                    TextInput::make('subtitle')
                        ->label(__('dashboard.testimonials_subtitle')),

                    Repeater::make('items')
                        ->label(__('dashboard.testimonials_items'))
                        ->hint(__('dashboard.testimonials_hint'))
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('customer_name')
                                    ->label(__('dashboard.testimonial_customer_name')),
                                TextInput::make('review')
                                    ->label(__('dashboard.testimonial_review')),
                                FileUpload::make('customer_image')
                                    ->label(__('dashboard.testimonial_customer_image'))
                                    ->image()
                                    ->directory('home/testimonials')
                                    ->optimize('webp'),
                                Select::make('rating')
                                    ->label(__('dashboard.testimonial_rating'))
                                    ->options([
                                        5 => '5 ⭐',
                                        4 => '4 ⭐',
                                        3 => '3 ⭐',
                                        2 => '2 ⭐',
                                        1 => '1 ⭐',
                                    ])
                                    ->default(5),
                            ]),
                        ])
                        ->columns(1)
                        ->defaultItems(1)
                        ->addActionLabel(__('dashboard.add_testimonial'))
                        ->cloneable(),
                ])
                ->visible(fn($get) => $get('type') === 'testimonials')
                ->columnSpanFull(),
        ];
    }

    private const SECTION_ALLOWED_KEYS = [
        'hero' => ['title', 'subtitle', 'button_text', 'button_url', 'image'],
        'categories' => ['title', 'category_ids'],
        'new_arrivals' => ['title', 'subtitle', 'products_count'],
        'best_sellers' => ['title', 'subtitle', 'products_count'],
        'deals' => ['title', 'subtitle', 'products_count'],
        'testimonials' => ['title', 'subtitle', 'items'],
    ];

    public function loadSections(): void
    {
        $sections = HomeSection::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($s) {
                $data = $s->content ?? [];
                $data['id'] = $s->id;
                $data['type'] = $s->type;
                $data['is_active'] = $s->is_active;
                $data['sort_order'] = $s->sort_order;

                $type = $s->type ?? null;
                $allowed = self::SECTION_ALLOWED_KEYS[$type] ?? [];
                $allowed[] = 'id';
                $allowed[] = 'type';
                $allowed[] = 'is_active';
                $allowed[] = 'sort_order';

                $filtered = collect($data)->only($allowed)->toArray();

                if (isset($filtered['category_ids']) && is_string($filtered['category_ids'])) {
                    $filtered['category_ids'] = json_decode($filtered['category_ids'], true) ?? [];
                }

                if (isset($filtered['items']) && is_string($filtered['items'])) {
                    $filtered['items'] = json_decode($filtered['items'], true) ?? [];
                }

                return $filtered;
            })
            ->toArray();

        $this->form->fill([
            'sections' => $sections,
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $sections = $data['sections'] ?? [];

        DB::transaction(function () use ($sections) {
            $submittedIds = [];

            foreach ($sections as $index => $section) {
                $type = $section['type'] ?? null;

                if (! $type) {
                    continue;
                }

                $excludeKeys = ['id', 'type', 'is_active', 'sort_order'];
                $content = collect($section)->except($excludeKeys)->toArray();

                $payload = [
                    'type' => $type,
                    'content' => $content,
                    'sort_order' => $section['sort_order'] ?? $index,
                    'is_active' => $section['is_active'] ?? true,
                ];

                $id = $section['id'] ?? null;

                if ($id) {
                    HomeSection::where('id', $id)->update($payload);
                    $submittedIds[] = $id;
                } else {
                    $record = HomeSection::create($payload);
                    $submittedIds[] = $record->id;
                }
            }

            HomeSection::whereNotIn('id', $submittedIds)->delete();
        });

        Notification::make()
            ->success()
            ->title(__('dashboard.home_sections_saved'))
            ->send();
    }
}
