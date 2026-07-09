<?php

namespace App\Filament\Tenant\Resources\WhatsAppWebhookEvents;

use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Tenant\Resources\WhatsAppWebhookEvents\Pages\ListWhatsAppWebhookEvents;
use App\Models\WhatsAppWebhookEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
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
        return $table
            ->columns([
                TextColumn::make('event_type')->label(__('dashboard.whatsapp_event_type')),
                TextColumn::make('phone_number_id')->label(__('dashboard.whatsapp_phone_number_id')),
                TextColumn::make('processing_status')->label(__('dashboard.whatsapp_processing_status'))->badge(),
                TextColumn::make('created_at')->label(__('dashboard.created_at'))->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppWebhookEvents::route('/'),
        ];
    }
}
