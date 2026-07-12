<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ContactUsSettings extends Page
{
    protected static ?int $navigationSort = 170;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_contact_us');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_contact_us');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('contact-us-settings.view');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-envelope';
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
                    Toggle::make('contact_us_section_active')
                        ->label(__('dashboard.contact_us_settings.section_active'))
                        ->default(true),

                    Section::make(__('dashboard.contact_us_content_section'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('contact_us_small_title_ar')
                                ->label(__('dashboard.contact_us_settings.small_title_ar')),

                            TextInput::make('contact_us_small_title_en')
                                ->label(__('dashboard.contact_us_settings.small_title_en')),

                            TextInput::make('contact_us_main_title_ar')
                                ->label(__('dashboard.contact_us_settings.main_title_ar')),

                            TextInput::make('contact_us_main_title_en')
                                ->label(__('dashboard.contact_us_settings.main_title_en')),

                            RichEditor::make('contact_us_description_ar')
                                ->label(__('dashboard.contact_us_settings.description_ar')),

                            RichEditor::make('contact_us_description_en')
                                ->label(__('dashboard.contact_us_settings.description_en')),

                            FileUpload::make('contact_us_image')
                                ->label(__('dashboard.contact_us_settings.image'))
                                ->image()
                                ->directory('contact-us')
                                ->columnSpan(2),
                        ])
                        ->icon(Heroicon::Photo)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.contact_us_info_section'))
                        ->columns(3)
                        ->schema([
                            TextInput::make('contact_us_email')
                                ->label(__('dashboard.contact_us_settings.email'))
                                ->email()
                                ->maxLength(255),

                            TextInput::make('contact_us_phone')
                                ->label(__('dashboard.contact_us_settings.phone'))
                                ->tel()
                                ->maxLength(255),

                            TextInput::make('contact_us_whatsapp')
                                ->label(__('dashboard.contact_us_settings.whatsapp'))
                                ->tel()
                                ->maxLength(255),
                        ])
                        ->icon(Heroicon::InformationCircle)
                        ->columnSpanFull(),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->submit('save')
                                ->label(__('dashboard.save'))
                                ->keyBindings(['mod+s'])
                                ->visible(fn () => Auth::user()->can('contact-us-settings.update')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! Auth::user()?->can('contact-us-settings.update')) {
            Notification::make()
                ->danger()
                ->title(__('dashboard.not_authorized'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        $richEditorKeys = ['contact_us_description_ar', 'contact_us_description_en'];

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
            ->title(__('dashboard.settings_saved_successfully'))
            ->send();
    }

    public function getRecord()
    {
        $keys = [
            'contact_us_section_active',
            'contact_us_small_title_ar', 'contact_us_small_title_en',
            'contact_us_main_title_ar', 'contact_us_main_title_en',
            'contact_us_description_ar', 'contact_us_description_en',
            'contact_us_image',
            'contact_us_email', 'contact_us_phone', 'contact_us_whatsapp',
        ];

        $richEditorKeys = ['contact_us_description_ar', 'contact_us_description_en'];

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
