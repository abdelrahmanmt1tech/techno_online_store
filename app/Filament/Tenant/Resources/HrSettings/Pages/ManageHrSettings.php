<?php

namespace App\Filament\Tenant\Resources\HrSettings\Pages;

use App\Filament\Tenant\Resources\HrSettings\HrSettingResource;
use App\Services\Hr\HrSettingsService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class ManageHrSettings extends EditRecord
{
    protected static string $resource = HrSettingResource::class;

    public function getTitle(): string
    {
        return __('hr.resources.settings');
    }

    public function mount(int|string|null $record = null): void
    {
        $settings = app(HrSettingsService::class)->getOrCreate();
        parent::mount($settings->getKey());
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('hr.notifications.settings_saved'));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('hr.actions.save'))
                ->submit('save'),
        ];
    }
}
