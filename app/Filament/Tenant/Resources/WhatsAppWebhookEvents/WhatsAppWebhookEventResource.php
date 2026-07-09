<?php

namespace App\Filament\Tenant\Resources\WhatsAppWebhookEvents;

use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Tables\WhatsAppWebhookEventsTable;
use App\Filament\Tenant\Resources\WhatsAppWebhookEvents\Pages\ListWhatsAppWebhookEvents;
use App\Filament\Tenant\Resources\WhatsAppWebhookEvents\Pages\ViewWhatsAppWebhookEvent;
use App\Models\WhatsAppWebhookEvent;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookEventResource extends Resource
{
    use ChecksWhatsAppPermissions;

    protected static ?string $model = WhatsAppWebhookEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Signal;

    protected static ?int $navigationSort = 44;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_webhook_events');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.whatsapp_webhook_events');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.whatsapp_webhook_event');
    }

    public static function canViewAny(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_webhook_events');
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()?->getTenantKey());
    }

    public static function table(Table $table): Table
    {
        return WhatsAppWebhookEventsTable::configure($table)
            ->filters([
                SelectFilter::make('processing_status')
                    ->label(__('dashboard.whatsapp_processing_status'))
                    ->options([
                        'pending' => __('dashboard.whatsapp_webhook_status_pending'),
                        'processed' => __('dashboard.whatsapp_webhook_status_processed'),
                        'failed' => __('dashboard.whatsapp_webhook_status_failed'),
                        'unresolved' => __('dashboard.whatsapp_webhook_status_unresolved'),
                        'rejected' => __('dashboard.whatsapp_webhook_status_rejected'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppWebhookEvents::route('/'),
            'view' => ViewWhatsAppWebhookEvent::route('/{record}'),
        ];
    }
}
