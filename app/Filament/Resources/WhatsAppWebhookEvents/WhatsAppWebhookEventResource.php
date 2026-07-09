<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents;

use App\Filament\Resources\WhatsAppWebhookEvents\Pages\ListWhatsAppWebhookEvents;
use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Models\WhatsAppWebhookEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
        return static::canWhatsAppPermission('whatsapp.view_webhook_events', 'whatsapp.platform.view_webhook_events');
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label(__('dashboard.whatsapp_tenant')),
                TextColumn::make('event_type')->label(__('dashboard.whatsapp_event_type')),
                TextColumn::make('phone_number_id')->label(__('dashboard.whatsapp_phone_number_id')),
                TextColumn::make('processing_status')->label(__('dashboard.whatsapp_processing_status'))->badge(),
                TextColumn::make('error_message')->label(__('dashboard.description'))->limit(40)->toggleable(),
                TextColumn::make('payload')
                    ->label(__('dashboard.whatsapp_payload'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : $state)
                    ->limit(50)
                    ->visible(fn () => Auth::user()?->can('whatsapp.platform.troubleshoot') ?? false),
                TextColumn::make('created_at')->label(__('dashboard.created_at'))->dateTime(),
            ])
            ->filters([
                SelectFilter::make('processing_status')
                    ->label(__('dashboard.whatsapp_processing_status'))
                    ->options([
                        'pending' => 'pending',
                        'processed' => 'processed',
                        'failed' => 'failed',
                        'unresolved' => 'unresolved',
                        'rejected' => 'rejected',
                    ]),
                SelectFilter::make('tenant_id')
                    ->label(__('dashboard.whatsapp_tenant'))
                    ->relationship('tenant', 'name'),
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
