<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
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

class HaveQuestionSettings extends Page
{
    protected static ?int $navigationSort = 160;

    protected string $view = 'filament-panels::pages.page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.nav_have_question');
    }

    public function getTitle(): string
    {
        return __('dashboard.nav_have_question');
    }

    public static function getNavigationGroup(): string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('have-question-settings.view');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
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
                    Toggle::make('have_question_section_active')
                        ->label(__('dashboard.have_question_settings.section_active'))
                        ->default(true),

                    Section::make(__('dashboard.have_question_content_section'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('have_question_title_ar')
                                ->label(__('dashboard.have_question_settings.title_ar')),

                            TextInput::make('have_question_title_en')
                                ->label(__('dashboard.have_question_settings.title_en')),

                            Textarea::make('have_question_description_ar')
                                ->label(__('dashboard.have_question_settings.description_ar'))
                                ->rows(5),

                            Textarea::make('have_question_description_en')
                                ->label(__('dashboard.have_question_settings.description_en'))
                                ->rows(5),

                            // TextInput::make('have_question_link')
                            //     ->label(__('dashboard.have_question_settings.link'))
                            //     ->url()
                            //     ->maxLength(255)
                            //     ->columnSpan(2),
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
                                ->visible(fn () => Auth::user()->can('have-question-settings.update')),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! Auth::user()?->can('have-question-settings.update')) {
            Notification::make()
                ->danger()
                ->title(__('dashboard.not_authorized'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        $richEditorKeys = ['have_question_description_ar', 'have_question_description_en'];

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
            'have_question_section_active',
            'have_question_title_ar', 'have_question_title_en',
            'have_question_description_ar', 'have_question_description_en',
            // 'have_question_link',
        ];

        $richEditorKeys = ['have_question_description_ar', 'have_question_description_en'];

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
