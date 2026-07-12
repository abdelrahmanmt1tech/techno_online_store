<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CodeSettings extends Page
{
    protected static ?int $navigationSort = 210;

    protected string $view = 'filament.pages.common-page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('dashboard.code_settings_page');
    }

    public function getTitle(): string
    {
        return __('dashboard.code_settings_page');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('dashboard.nav_group_settings');
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('code-settings.view') ?? false;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-code-bracket';
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('dashboard.code_settings_page'))
                        ->schema([
                            Textarea::make('custom_head_code')
                                ->label(__('dashboard.custom_head_code'))
                                ->rows(8)
                                ->helperText(__('dashboard.custom_head_code_help')),

                            Textarea::make('custom_footer_code')
                                ->label(__('dashboard.custom_footer_code'))
                                ->rows(8)
                                ->helperText(__('dashboard.custom_footer_code_help')),
                        ])
                        ->columns(1),
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
                ['value' => $value ?? null],
            );
        }

        Notification::make()
            ->success()
            ->title(__('dashboard.saved_successfully'))
            ->send();
    }

    public function getRecord()
    {
        $keys = [
            'custom_head_code',
            'custom_footer_code',
        ];

        $settings = [];

        foreach ($keys as $key) {
            $settings[$key] = Setting::where('key', $key)->value('value') ?? '';
        }

        return collect($settings);
    }
}
