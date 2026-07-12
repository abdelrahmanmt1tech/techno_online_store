<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
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
use Illuminate\Support\Facades\Auth;

class ShippingCompaniesSettings extends Page
{
    protected static ?int $navigationSort = 120;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_shipping_companies');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_shipping_companies');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('shipping-companies-settings.view');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-truck';
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
                    Toggle::make('shipping_companies_section_active')
                        ->label(__('dashboard.shipping_companies_settings.section_active'))
                        ->default(true),

                    Section::make(__('dashboard.shipping_companies_content_section'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('shipping_companies_small_title_ar')
                                ->label(__('dashboard.shipping_companies_settings.small_title_ar')),

                            TextInput::make('shipping_companies_small_title_en')
                                ->label(__('dashboard.shipping_companies_settings.small_title_en')),

                            TextInput::make('shipping_companies_main_title_ar')
                                ->label(__('dashboard.shipping_companies_settings.main_title_ar')),

                            TextInput::make('shipping_companies_main_title_en')
                                ->label(__('dashboard.shipping_companies_settings.main_title_en')),

                            RichEditor::make('shipping_companies_description_ar')
                                ->label(__('dashboard.shipping_companies_settings.description_ar')),

                            RichEditor::make('shipping_companies_description_en')
                                ->label(__('dashboard.shipping_companies_settings.description_en')),

                            FileUpload::make('shipping_companies_image')
                                ->label(__('dashboard.shipping_companies_settings.image'))
                                ->image()
                                ->directory('shipping-companies')
                                ->columnSpan(2),
                        ])
                        ->icon(Heroicon::Photo)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.shipping_companies_features_section'))
                        ->schema([
                            Repeater::make('shipping_companies_features')
                                ->label(__('dashboard.shipping_companies_settings.features'))
                                ->collapsible()
                                ->defaultItems(0)
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('title_ar')
                                                ->label(__('dashboard.shipping_companies_settings.feature_title_ar'))
                                                ->required(),

                                            TextInput::make('title_en')
                                                ->label(__('dashboard.shipping_companies_settings.feature_title_en'))
                                                ->required(),
                                        ]),
                                ])
                                ->addActionLabel(__('dashboard.shipping_companies_settings.add_feature')),
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
                                ->keyBindings(['mod+s'])
                                ->visible(fn () => Auth::user()->can('shipping-companies-settings.update')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! Auth::user()?->can('shipping-companies-settings.update')) {
            Notification::make()
                ->danger()
                ->title(__('dashboard.not_authorized'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        $richEditorKeys = ['shipping_companies_description_ar', 'shipping_companies_description_en'];
        $jsonKeys = ['shipping_companies_features'];

        foreach ($data as $key => $value) {
            if (in_array($key, $richEditorKeys)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['string_value' => $value ?? null]
                );
            } elseif (in_array($key, $jsonKeys)) {
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
            'shipping_companies_section_active',
            'shipping_companies_small_title_ar', 'shipping_companies_small_title_en',
            'shipping_companies_main_title_ar', 'shipping_companies_main_title_en',
            'shipping_companies_description_ar', 'shipping_companies_description_en',
            'shipping_companies_image',
            'shipping_companies_features',
        ];

        $richEditorKeys = ['shipping_companies_description_ar', 'shipping_companies_description_en'];
        $jsonKeys = ['shipping_companies_features'];

        $settings = [];

        foreach ($keys as $key) {
            $setting = Setting::where('key', $key)->first();

            $value = match (true) {
                in_array($key, $richEditorKeys) => $setting->string_value ?? '',
                in_array($key, $jsonKeys) => $setting ? json_decode($setting->value, true) : [],
                default => $setting->value ?? '',
            };

            $settings[$key] = $value;
        }

        return collect($settings);
    }
}
