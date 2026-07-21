<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
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

class GeneralSettings extends Page
{
    protected static ?int $navigationSort = 200;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_general');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_general');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.settings_group');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-6-tooth';
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
                    Section::make(__('dashboard.general_settings_section'))
                        ->columns(2)
                        ->schema([
                            FileUpload::make('dashboard_logo')
                                ->label(__('dashboard.general_settings.dashboard_logo'))
                                ->image()
                                ->directory('general'),

                            FileUpload::make('site_logo')
                                ->label(__('dashboard.general_settings.site_logo'))
                                ->image()
                                ->directory('general'),

                            FileUpload::make('admin_favicon')
                                ->label(__('dashboard.general_settings.admin_favicon'))
                                ->image()
                                ->directory('general'),

                            FileUpload::make('web_favicon')
                                ->label(__('dashboard.general_settings.web_favicon'))
                                ->image()
                                ->directory('general'),

                            TextInput::make('site_name')
                                ->label(__('dashboard.general_settings.site_name'))
                                ->maxLength(255)
                                ->columnSpan(1),
                        ])
                        ->icon(Heroicon::Cog6Tooth)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.homepage_seo'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('home_meta_title_ar')
                                ->label(__('dashboard.general_settings.home_meta_title_ar')),

                            TextInput::make('home_meta_title_en')
                                ->label(__('dashboard.general_settings.home_meta_title_en')),

                            Textarea::make('home_meta_description_ar')
                                ->label(__('dashboard.general_settings.home_meta_description_ar'))
                                ->rows(3),

                            Textarea::make('home_meta_description_en')
                                ->label(__('dashboard.general_settings.home_meta_description_en'))
                                ->rows(3),

                            TagsInput::make('home_keywords_ar')
                                ->label(__('dashboard.general_settings.home_keywords_ar'))
                                ->placeholder(__('dashboard.keywords_ar_placeholder'))
                                ->separator(' '),

                            TagsInput::make('home_keywords_en')
                                ->label(__('dashboard.general_settings.home_keywords_en'))
                                ->placeholder(__('dashboard.keywords_en_placeholder'))
                                ->separator(' '),

                            TextInput::make('home_canonical_url')
                                ->label(__('dashboard.general_settings.canonical_url'))
                                ->url()
                                ->nullable(),

                            FileUpload::make('home_og_image')
                                ->label(__('dashboard.general_settings.og_image'))
                                ->directory('seo')
                                ->image()
                                ->optimize('webp'),
                        ])
                        ->icon('heroicon-o-globe-alt')
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

        $tagInputKeys = ['home_keywords_ar', 'home_keywords_en'];

        foreach ($data as $key => $value) {
            if (in_array($key, $tagInputKeys)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => is_array($value) ? implode(', ', $value) : $value]
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
        $valueKeys = [
            'site_logo',
            'site_name',
            'admin_favicon',
            'web_favicon',
            'dashboard_logo',
            'header_color',
            'footer_color',
            'home_meta_title_ar',
            'home_meta_title_en',
            'home_meta_description_ar',
            'home_meta_description_en',
            'home_keywords_ar',
            'home_keywords_en',
            'home_canonical_url',
            'home_og_image',
        ];

        $tagInputKeys = ['home_keywords_ar', 'home_keywords_en'];

        $settings = [];

        foreach ($valueKeys as $key) {
            $value = Setting::where('key', $key)->value('value') ?? '';

            if (in_array($key, $tagInputKeys)) {
                $settings[$key] = $value !== '' ? array_map('trim', explode(' ', $value)) : [];
            } else {
                $settings[$key] = $value;
            }
        }

        return collect($settings);
    }
}
