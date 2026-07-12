<?php

namespace App\Filament\Tenant\Resources\MessengerWebhookEvents;

use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Shared\Messenger\Tables\MessengerWebhookEventsTable;
use App\Filament\Tenant\Resources\MessengerWebhookEvents\Pages\ListMessengerWebhookEvents;
use App\Filament\Tenant\Resources\MessengerWebhookEvents\Pages\ViewMessengerWebhookEvent;
use App\Models\MessengerWebhookEvent;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MessengerWebhookEventResource extends Resource
{
    use ChecksMessengerPermissions;

    protected static ?string $model = MessengerWebhookEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Signal;

    protected static ?int $navigationSort = 52;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.messenger_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.messenger_webhook_events');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.messenger_webhook_events');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.messenger_webhook_event');
    }

    public static function canViewAny(): bool
    {
        return static::canMessengerPermission('messenger.view_webhook_events');
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
        return MessengerWebhookEventsTable::configure($table)
            ->filters([
                SelectFilter::make('processing_status')
                    ->label(__('dashboard.messenger_processing_status'))
                    ->options([
                        'pending' => __('dashboard.messenger_webhook_status_pending'),
                        'processed' => __('dashboard.messenger_webhook_status_processed'),
                        'failed' => __('dashboard.messenger_webhook_status_failed'),
                        'unresolved' => __('dashboard.messenger_webhook_status_unresolved'),
                        'rejected' => __('dashboard.messenger_webhook_status_rejected'),
                    ]),
                SelectFilter::make('page_id')
                    ->label(__('dashboard.messenger_page_id'))
                    ->options(fn () => MessengerWebhookEvent::query()
                        ->where('tenant_id', tenant()?->getTenantKey())
                        ->whereNotNull('page_id')
                        ->distinct()
                        ->orderBy('page_id')
                        ->pluck('page_id', 'page_id')
                        ->all()),
                SelectFilter::make('event_type')
                    ->label(__('dashboard.messenger_event_type'))
                    ->options(fn () => MessengerWebhookEvent::query()
                        ->where('tenant_id', tenant()?->getTenantKey())
                        ->whereNotNull('event_type')
                        ->distinct()
                        ->orderBy('event_type')
                        ->pluck('event_type', 'event_type')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessengerWebhookEvents::route('/'),
            'view' => ViewMessengerWebhookEvent::route('/{record}'),
        ];
    }
}
