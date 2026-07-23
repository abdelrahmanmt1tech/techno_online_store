<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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

class FooterSettings extends Page
{
    protected static ?int $navigationSort = 210;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_footer');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_footer');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-bars-3-bottom-right';
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
                    Section::make(__('dashboard.footer_content_section'))
                        ->columns(2)
                        ->schema([
                            Textarea::make('footer_description')
                                ->label(__('dashboard.footer_settings.description'))
                                ->rows(5),

                            FileUpload::make('footer_logo')
                                ->label(__('dashboard.footer_settings.logo'))
                                ->image()
                                ->directory('footer')
                                ->columnSpanFull(),
                        ])
                        ->icon(Heroicon::Photo)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.footer_social_section'))
                        ->columns(3)
                        ->schema([
                            TextInput::make('footer_facebook')
                                ->label(__('dashboard.footer_settings.facebook'))
                                ->url()
                                ->maxLength(255)
                                ->prefix('https://facebook.com/'),

                            TextInput::make('footer_instagram')
                                ->label(__('dashboard.footer_settings.instagram'))
                                ->url()
                                ->maxLength(255)
                                ->prefix('https://instagram.com/'),

                            TextInput::make('footer_tiktok')
                                ->label(__('dashboard.footer_settings.tiktok'))
                                ->url()
                                ->maxLength(255)
                                ->prefix('https://tiktok.com/'),

                            TextInput::make('footer_youtube')
                                ->label(__('dashboard.footer_settings.youtube'))
                                ->url()
                                ->maxLength(255)
                                ->prefix('https://youtube.com/'),

                            TextInput::make('footer_x')
                                ->label(__('dashboard.footer_settings.x'))
                                ->url()
                                ->maxLength(255)
                                ->prefix('https://x.com/'),

                            TextInput::make('footer_linkedin')
                                ->label(__('dashboard.footer_settings.linkedin'))
                                ->url()
                                ->maxLength(255)
                                ->prefix('https://linkedin.com/'),
                        ])
                        ->icon(Heroicon::Link)
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

        $richEditorKeys = ['footer_description'];

        foreach ($data as $key => $value) {
            if (in_array($key, $richEditorKeys)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['string_value' => $value ?? null]
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
            ->title(__('dashboard.saved_successfully'))
            ->send();
    }

    public function getRecord()
    {
        $keys = [
            'footer_logo',
            'footer_description',
            'footer_facebook',
            'footer_instagram',
            'footer_tiktok',
            'footer_youtube',
            'footer_x',
            'footer_linkedin',
        ];

        $richEditorKeys = ['footer_description'];

        $settings = [];

        foreach ($keys as $key) {
            $setting = Setting::where('key', $key)->first();

            $value = in_array($key, $richEditorKeys)
                ? ($setting->string_value ?? '')
                : ($setting->value ?? '');

            $settings[$key] = $value;
        }

        return collect($settings);
    }
}
