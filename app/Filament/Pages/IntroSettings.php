<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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

class IntroSettings extends Page
{
    protected static ?int $navigationSort = 85;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_intro');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_intro');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('intro-settings.view');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-book-open';
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
                    Toggle::make('intro_section_active')
                        ->label(__('dashboard.intro_settings.section_active'))
                        ->default(true),

                    Section::make(__('dashboard.intro_content_section'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('intro_title_ar')
                                ->label(__('dashboard.intro_settings.title_ar')),

                            TextInput::make('intro_title_en')
                                ->label(__('dashboard.intro_settings.title_en')),

                            Textarea::make('intro_description_ar')
                                ->label(__('dashboard.intro_settings.description_ar'))
                                ->rows(5),

                            Textarea::make('intro_description_en')
                                ->label(__('dashboard.intro_settings.description_en'))
                                ->rows(5),

                            FileUpload::make('intro_image')
                                ->label(__('dashboard.intro_settings.image'))
                                ->image()
                                ->directory('intro')
                                ->optimize('webp')
                                ->columnSpan(2),

                            // TextInput::make('intro_link')
                            //     ->label(__('dashboard.intro_settings.link'))
                            //     ->url()
                            //     ->maxLength(255)
                            //     ->columnSpan(1),
                        ])
                        ->icon(Heroicon::Photo)
                        ->columnSpanFull(),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->submit('save')
                                ->label(__('dashboard.save'))
                                ->keyBindings(['mod+s'])
                                ->visible(fn () => Auth::user()->can('intro-settings.update')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! Auth::user()?->can('intro-settings.update')) {
            Notification::make()
                ->danger()
                ->title(__('dashboard.not_authorized'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        $richEditorKeys = ['intro_description_ar', 'intro_description_en'];

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
            'intro_section_active',
            'intro_title_ar', 'intro_title_en',
            'intro_description_ar', 'intro_description_en',
            'intro_image', // 'intro_link',
        ];

        $richEditorKeys = ['intro_description_ar', 'intro_description_en'];

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
