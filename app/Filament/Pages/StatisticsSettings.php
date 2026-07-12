<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
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

class StatisticsSettings extends Page
{
    protected static ?int $navigationSort = 87;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_statistics');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_statistics');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->toArray());
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
                    Toggle::make('statistics_section_active')
                        ->label(__('dashboard.statistics_settings.section_active'))
                        ->default(true),

                    Section::make(__('dashboard.statistics_content_section'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('statistics_title_ar')
                                ->label(__('dashboard.statistics_settings.title_ar'))
                                ->required(),

                            TextInput::make('statistics_title_en')
                                ->label(__('dashboard.statistics_settings.title_en'))
                                ->required(),

                            TextInput::make('statistics_description_ar')
                                ->label(__('dashboard.statistics_settings.description_ar')),

                            TextInput::make('statistics_description_en')
                                ->label(__('dashboard.statistics_settings.description_en')),
                        ])
                        ->icon(Heroicon::DocumentText)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.statistics_items_section'))
                        ->schema([
                            Repeater::make('statistics_items')
                                ->label(__('dashboard.statistics_settings.items'))
                                ->collapsible()
                                ->defaultItems(1)
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('title_ar')
                                                ->label(__('dashboard.statistics_settings.item_title_ar'))
                                                ->required(),

                                            TextInput::make('title_en')
                                                ->label(__('dashboard.statistics_settings.item_title_en'))
                                                ->required(),

                                            TextInput::make('value')
                                                ->label(__('dashboard.statistics_settings.item_value'))
                                                ->required()
                                                ->numeric(),
                                        ]),

                                ])
                                ->addActionLabel(__('dashboard.statistics_settings.add_item')),
                        ])
                        ->icon(Heroicon::QueueList)
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

    public function save(): void
    {
        $data = $this->form->getState();
        $jsonKeys = ['statistics_items'];

        foreach ($data as $key => $value) {
            if (in_array($key, $jsonKeys)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => ! empty($value) ? json_encode($value) : null]
                );
            } else {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        Notification::make()
            ->success()
            ->title(__('dashboard.settings_saved_successfully'))
            ->send();
    }

    public function getRecord()
    {
        $keys = [
            'statistics_section_active',
            'statistics_title_ar',
            'statistics_title_en',
            'statistics_description_ar',
            'statistics_description_en',
            'statistics_items',
        ];

        $jsonKeys = ['statistics_items'];

        $settings = [];

        foreach ($keys as $key) {
            $setting = Setting::where('key', $key)->first();

            $value = match (true) {
                in_array($key, $jsonKeys) => $setting ? json_decode($setting->value, true) : [],
                default => $setting->value ?? '',
            };

            $settings[$key] = $value;
        }

        return collect($settings);
    }
}
