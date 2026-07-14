<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
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

class PaymentGatewaysSettings extends Page
{
    protected static ?int $navigationSort = 110;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_payment_gateways');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_payment_gateways');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('payment-gateways-settings.view');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-credit-card';
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
                    Toggle::make('payment_gateways_section_active')
                        ->label(__('dashboard.payment_gateways_settings.section_active'))
                        ->default(true),

                    Section::make(__('dashboard.payment_gateways_content_section'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('payment_gateways_small_title_ar')
                                ->label(__('dashboard.payment_gateways_settings.small_title_ar')),

                            TextInput::make('payment_gateways_small_title_en')
                                ->label(__('dashboard.payment_gateways_settings.small_title_en')),

                            TextInput::make('payment_gateways_main_title_ar')
                                ->label(__('dashboard.payment_gateways_settings.main_title_ar')),

                            TextInput::make('payment_gateways_main_title_en')
                                ->label(__('dashboard.payment_gateways_settings.main_title_en')),

                            Textarea::make('payment_gateways_description_ar')
                                ->label(__('dashboard.payment_gateways_settings.description_ar'))
                                ->rows(5),

                            Textarea::make('payment_gateways_description_en')
                                ->label(__('dashboard.payment_gateways_settings.description_en'))
                                ->rows(5),

                            FileUpload::make('payment_gateways_image')
                                ->label(__('dashboard.payment_gateways_settings.image'))
                                ->image()
                                ->directory('payment-gateways')
                                ->columnSpan(2),
                        ])
                        ->icon(Heroicon::Photo)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.payment_gateways_features_section'))
                        ->schema([
                            Repeater::make('payment_gateways_features')
                                ->label(__('dashboard.payment_gateways_settings.features'))
                                ->collapsible()
                                ->defaultItems(0)
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('title_ar')
                                                ->label(__('dashboard.payment_gateways_settings.feature_title_ar'))
                                                ->required(),

                                            TextInput::make('title_en')
                                                ->label(__('dashboard.payment_gateways_settings.feature_title_en'))
                                                ->required(),
                                        ]),
                                ])
                                ->addActionLabel(__('dashboard.payment_gateways_settings.add_feature')),
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
                                ->visible(fn () => Auth::user()->can('payment-gateways-settings.update')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! Auth::user()?->can('payment-gateways-settings.update')) {
            Notification::make()
                ->danger()
                ->title(__('dashboard.not_authorized'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        $richEditorKeys = ['payment_gateways_description_ar', 'payment_gateways_description_en'];
        $jsonKeys = ['payment_gateways_features'];

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
            'payment_gateways_section_active',
            'payment_gateways_small_title_ar', 'payment_gateways_small_title_en',
            'payment_gateways_main_title_ar', 'payment_gateways_main_title_en',
            'payment_gateways_description_ar', 'payment_gateways_description_en',
            'payment_gateways_image',
            'payment_gateways_features',
        ];

        $richEditorKeys = ['payment_gateways_description_ar', 'payment_gateways_description_en'];
        $jsonKeys = ['payment_gateways_features'];

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
