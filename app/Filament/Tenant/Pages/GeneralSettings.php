<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public static function canAccess(): bool
    {
        return Auth::user()->can('settings.general.view');
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

                            ColorPicker::make('site_color')
                                ->label(__('dashboard.general_settings.site_color')),

                            Select::make('site_font')
                                ->label(__('dashboard.general_settings.site_font'))
                                ->options([
                                    __('dashboard.general_settings.arabic_fonts') => [
                                        'Cairo' => __('dashboard.general_settings.font_Cairo'),
                                        'Tajawal' => __('dashboard.general_settings.font_Tajawal'),
                                        'IBM Plex Sans Arabic' => __('dashboard.general_settings.font_IBM Plex Sans Arabic'),
                                        'El Messiri' => __('dashboard.general_settings.font_El Messiri'),
                                        'Mada' => __('dashboard.general_settings.font_Mada'),
                                        'Readex Pro' => __('dashboard.general_settings.font_Readex Pro'),
                                        'Changa' => __('dashboard.general_settings.font_Changa'),
                                        'Noto Sans Arabic' => __('dashboard.general_settings.font_Noto Sans Arabic'),
                                    ],
                                    __('dashboard.general_settings.english_fonts') => [
                                        'Raleway' => __('dashboard.general_settings.font_Raleway'),
                                        'Unna' => __('dashboard.general_settings.font_Unna'),
                                        'Wittgenstein' => __('dashboard.general_settings.font_Wittgenstein'),
                                        'Baskerville' => __('dashboard.general_settings.font_Baskerville'),
                                        'Nunito Sans' => __('dashboard.general_settings.font_Nunito Sans'),
                                        'Didact Gothic' => __('dashboard.general_settings.font_Didact Gothic'),
                                        'Hind' => __('dashboard.general_settings.font_Hind'),
                                    ],
                                ])
                                ->searchable()
                                ->columnSpan(1),

                            Select::make('site_language')
                                ->label(__('dashboard.general_settings.site_language'))
                                ->options([
                                    'en' => __('dashboard.general_settings.lang_en'),
                                    'ar' => __('dashboard.general_settings.lang_ar'),
                                ])
                                ->columnSpan(1),

                            Select::make('site_currency')
                                ->label(__('dashboard.general_settings.site_currency'))
                                ->options(function () {
                                    $currencies = DB::connection(
                                        config('tenancy.database.central_connection', config('database.default'))
                                    )
                                        ->table('currencies')
                                        ->where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->get();

                                    $locale = app()->getLocale();

                                    return $currencies->mapWithKeys(function ($currency) use ($locale) {
                                        $name = json_decode($currency->name, true)[$locale] ?? $currency->code;

                                        return [$currency->code => $currency->code.' - '.$name];
                                    })->toArray();
                                })
                                ->searchable()
                                ->columnSpan(1),
                        ])
                        ->icon(Heroicon::Cog6Tooth)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.homepage_seo'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('home_meta_title')
                                ->label(__('dashboard.general_settings.home_meta_title')),

                            Textarea::make('home_meta_description')
                                ->label(__('dashboard.general_settings.home_meta_description'))
                                ->rows(3),

                            TagsInput::make('home_keywords')
                                ->label(__('dashboard.general_settings.home_keywords'))
                                ->placeholder(__('dashboard.keywords_placeholder'))
                                ->separator(' '),

                            TextInput::make('home_canonical_url')
                                ->label(__('dashboard.general_settings.canonical_url'))
                                ->url()
                                ->nullable(),

                            FileUpload::make('home_og_image')
                                ->label(__('dashboard.general_settings.og_image'))
                                ->directory('seo')
                                ->image()
                                ->optimize('webp')
                                ->columnSpanFull(),
                        ])
                        ->icon('heroicon-o-globe-alt')
                        ->columnSpanFull(),

                    Section::make(__('dashboard.registration_terms'))
                        ->columns(2)
                        ->schema([
                            Textarea::make('registration_terms')
                                ->label(__('dashboard.registration_terms_content'))
                                ->helperText(__('dashboard.registration_terms_helper'))
                                ->rows(8)
                                ->columnSpanFull(),
                        ])
                        ->icon('heroicon-o-document-text')
                        ->columnSpanFull(),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->submit('save')
                                ->label(__('dashboard.save'))
                                ->keyBindings(['mod+s'])
                                ->visible(fn () => Auth::user()->can('settings.general.update')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! Auth::user()->can('settings.general.update')) {
            Notification::make()
                ->danger()
                ->title(__('dashboard.not_authorized'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        $tagInputKeys = ['home_keywords'];

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
            'site_color',
            'admin_favicon',
            'web_favicon',
            'dashboard_logo',
            'header_color',
            'footer_color',
            'home_meta_title',
            'home_meta_description',
            'home_keywords',
            'home_canonical_url',
            'home_og_image',
            'site_font',
            'site_language',
            'site_currency',
            'registration_terms',
        ];

        $tagInputKeys = ['home_keywords'];

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
