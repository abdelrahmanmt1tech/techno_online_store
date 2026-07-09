<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class AboutSettings extends Page
{
    protected static ?int $navigationSort = 86;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_about');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_about');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('about-settings.view');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-information-circle';
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
                    Section::make(__('dashboard.about_content_section'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('about_small_title_ar')
                                ->label(__('dashboard.about_settings.small_title_ar')),

                            TextInput::make('about_small_title_en')
                                ->label(__('dashboard.about_settings.small_title_en')),

                            TextInput::make('about_main_title_ar')
                                ->label(__('dashboard.about_settings.main_title_ar')),

                            TextInput::make('about_main_title_en')
                                ->label(__('dashboard.about_settings.main_title_en')),

                            RichEditor::make('about_description_ar')
                                ->label(__('dashboard.about_settings.description_ar')),

                            RichEditor::make('about_description_en')
                                ->label(__('dashboard.about_settings.description_en')),
                        ])
                        ->icon(Heroicon::Photo)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.about_features_section'))
                        ->schema([
                            Repeater::make('about_features')
                                ->label(__('dashboard.about_settings.features'))
                                ->collapsible()
                                ->defaultItems(0)
                                ->schema([
                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('title_ar')
                                                ->label(__('dashboard.about_settings.feature_title_ar'))
                                                ->required(),

                                            TextInput::make('title_en')
                                                ->label(__('dashboard.about_settings.feature_title_en'))
                                                ->required(),
                                        ]),

                                    Grid::make()
                                        ->columns(2)
                                        ->schema([
                                            Textarea::make('description_ar')
                                                ->label(__('dashboard.about_settings.feature_description_ar'))
                                                ->rows(3),

                                            Textarea::make('description_en')
                                                ->label(__('dashboard.about_settings.feature_description_en'))
                                                ->rows(3),
                                        ]),

                                    FileUpload::make('image')
                                        ->label(__('dashboard.about_settings.feature_image'))
                                        ->image()
                                        ->directory('about'),
                                ])
                                ->addActionLabel(__('dashboard.about_settings.add_feature')),
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
                                ->visible(fn () => Auth::user()->can('about-settings.update')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! Auth::user()?->can('about-settings.update')) {
            Notification::make()
                ->danger()
                ->title(__('dashboard.not_authorized'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        $richEditorKeys = ['about_description_ar', 'about_description_en'];
        $jsonKeys = ['about_features'];

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
            'about_small_title_ar', 'about_small_title_en',
            'about_main_title_ar', 'about_main_title_en',
            'about_description_ar', 'about_description_en',
            'about_features',
        ];

        $richEditorKeys = ['about_description_ar', 'about_description_en'];
        $jsonKeys = ['about_features'];

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
