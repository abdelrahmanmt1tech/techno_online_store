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

class ContactUsSettings extends Page
{
    protected static ?int $navigationSort = 80;

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

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-phone';
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
                    Section::make(__('dashboard.contact_us_content_section'))
                        ->columns()
                        ->schema([
                            TextInput::make('contact_us_title')
                                ->label(__('dashboard.contact_us_settings.title')),
                                
                            Textarea::make('contact_us_description')
                                ->label(__('dashboard.contact_us_settings.description'))
                                ->rows(4),

                            FileUpload::make('contact_us_image')
                                ->label(__('dashboard.contact_us_settings.image'))
                                ->image()
                                ->directory('contact-us')
                                ->columnSpanFull(),
                        ])
                        ->icon(Heroicon::InformationCircle)
                        ->columnSpanFull(),

                    Section::make(__('dashboard.contact_us_info_section'))
                        ->columns(3)
                        ->schema([
                            TextInput::make('contact_us_email')
                                ->label(__('dashboard.contact_us_settings.email'))
                                ->email()
                                ->columnSpan(1),

                            TextInput::make('contact_us_phone')
                                ->label(__('dashboard.contact_us_settings.phone'))
                                ->tel()
                                ->columnSpan(1),

                            TextInput::make('contact_us_whatsapp')
                                ->label(__('dashboard.contact_us_settings.whatsapp'))
                                ->tel()
                                ->columnSpan(1),
                        ])
                        ->icon(Heroicon::ChatBubbleLeftRight)
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

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Notification::make()
            ->success()
            ->title(__('dashboard.settings_saved_successfully'))
            ->send();
    }

    public function getRecord()
    {
        $keys = [
            'contact_us_title',
            'contact_us_description',
            'contact_us_image',
            'contact_us_email',
            'contact_us_phone',
            'contact_us_whatsapp',
        ];

        $settings = [];

        foreach ($keys as $key) {
            $settings[$key] = Setting::where('key', $key)->value('value') ?? '';
        }

        return collect($settings);
    }
}
