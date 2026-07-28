<?php

namespace App\Filament\Tenant\Resources\PosSettings\Pages;

use App\Filament\Tenant\Resources\PosSettings\PosSettingResource;
use App\Services\Pos\CashierSessionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class ManagePosSettings extends EditRecord
{
    protected static string $resource = PosSettingResource::class;

    public function getTitle(): string
    {
        return __('commerce.resources.pos_settings');
    }

    public function mount(int|string|null $record = null): void
    {
        $settings = app(CashierSessionService::class)->settings();
        parent::mount($settings->getKey());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::guard('tenant')->id();

        return $data;
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
            ->title(__('erp.notifications.settings_saved'));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('erp.actions.save'))
                ->submit('save'),
        ];
    }
}
