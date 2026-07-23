<?php

namespace App\Filament\Tenant\Resources\InvoicePrintSettings\Pages;

use App\Filament\Tenant\Resources\InvoicePrintSettings\InvoicePrintSettingResource;
use App\Services\Erp\InvoicePrintSettingsService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class ManageInvoicePrintSettings extends EditRecord
{
    protected static string $resource = InvoicePrintSettingResource::class;

    public function getTitle(): string
    {
        return __('erp.resources.invoice_print_settings');
    }

    public function mount(int|string|null $record = null): void
    {
        $settings = app(InvoicePrintSettingsService::class)->getOrCreate();
        parent::mount($settings->getKey());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::guard('tenant')->id();

        // دمج display_options مع الافتراضيات
        $defaults = app(InvoicePrintSettingsService::class)->defaultDisplayOptions();
        $data['display_options'] = array_merge($defaults, $data['display_options'] ?? []);

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
