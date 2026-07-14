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

class TrainingSupportSettings extends Page
{
    protected static ?int $navigationSort = 140;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_training_support');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_training_support');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('training-support-settings.view');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-academic-cap';
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
                    Toggle::make('training_support_section_active')
                        ->label(__('dashboard.training_support_settings.section_active'))
                        ->default(true),

                    Section::make(__('dashboard.training_support_content_section'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('training_support_small_title_ar')
                                ->label(__('dashboard.training_support_settings.small_title_ar')),

                            TextInput::make('training_support_small_title_en')
                                ->label(__('dashboard.training_support_settings.small_title_en')),

                            TextInput::make('training_support_main_title_ar')
                                ->label(__('dashboard.training_support_settings.main_title_ar')),

                            TextInput::make('training_support_main_title_en')
                                ->label(__('dashboard.training_support_settings.main_title_en')),

                            Textarea::make('training_support_description_ar')
                                ->label(__('dashboard.training_support_settings.description_ar'))
                                ->rows(5),

                            Textarea::make('training_support_description_en')
                                ->label(__('dashboard.training_support_settings.description_en'))
                                ->rows(5),
                        ])
                        ->icon(Heroicon::Photo)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.training_support_items_section'))
                        ->schema([
                            Repeater::make('training_support_items')
                                ->label(__('dashboard.training_support_settings.items'))
                                ->collapsible()
                                ->defaultItems(0)
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('title_ar')
                                                ->label(__('dashboard.training_support_settings.item_title_ar'))
                                                ->required(),

                                            TextInput::make('title_en')
                                                ->label(__('dashboard.training_support_settings.item_title_en'))
                                                ->required(),
                                        ]),

                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            Textarea::make('description_ar')
                                                ->label(__('dashboard.training_support_settings.item_description_ar'))
                                                ->rows(3),

                                            Textarea::make('description_en')
                                                ->label(__('dashboard.training_support_settings.item_description_en'))
                                                ->rows(3),
                                        ]),

                                    FileUpload::make('image')
                                        ->label(__('dashboard.training_support_settings.item_image'))
                                        ->image()
                                        ->directory('training-support'),
                                ])
                                ->addActionLabel(__('dashboard.training_support_settings.add_item')),
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
                                ->visible(fn () => Auth::user()->can('training-support-settings.update')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! Auth::user()?->can('training-support-settings.update')) {
            Notification::make()
                ->danger()
                ->title(__('dashboard.not_authorized'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        $richEditorKeys = ['training_support_description_ar', 'training_support_description_en'];
        $jsonKeys = ['training_support_items'];

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
            'training_support_section_active',
            'training_support_small_title_ar', 'training_support_small_title_en',
            'training_support_main_title_ar', 'training_support_main_title_en',
            'training_support_description_ar', 'training_support_description_en',
            'training_support_items',
        ];

        $richEditorKeys = ['training_support_description_ar', 'training_support_description_en'];
        $jsonKeys = ['training_support_items'];

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
