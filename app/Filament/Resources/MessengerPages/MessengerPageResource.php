<?php

namespace App\Filament\Resources\MessengerPages;

use App\Filament\Resources\MessengerPages\Pages\ListMessengerPages;
use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Shared\Messenger\Tables\MessengerPagesTable;
use App\Messenger\Actions\SyncMessengerPageStatusAction;
use App\Messenger\Enums\MessengerPageStatus;
use App\Models\MessengerPageRegistry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MessengerPageResource extends Resource
{
    use ChecksMessengerPermissions;

    protected static ?string $model = MessengerPageRegistry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.messenger_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.messenger_pages');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.messenger_pages');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.messenger_page');
    }

    public static function canViewAny(): bool
    {
        return static::canMessengerPermission('messenger.view_pages', 'messenger.platform.view_all_pages');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canMessengerPermission('messenger.manage_pages', 'messenger.platform.manage_all_pages');
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
        return MessengerPagesTable::configure($table, showTenant: true, forRegistry: true)
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label(__('dashboard.messenger_tenant'))
                    ->relationship('tenant', 'name'),
                SelectFilter::make('status')
                    ->label(__('dashboard.messenger_connection_status'))
                    ->options(collect(MessengerPageStatus::cases())->mapWithKeys(
                        fn (MessengerPageStatus $status) => [$status->value => __('dashboard.messenger_status_'.$status->value)]
                    )),
                SelectFilter::make('is_active')
                    ->label(__('dashboard.active'))
                    ->options([
                        '1' => __('dashboard.yes'),
                        '0' => __('dashboard.no'),
                    ]),
            ])
            ->recordActions([
                Action::make('disable')
                    ->label(__('dashboard.messenger_disable'))
                    ->icon(Heroicon::NoSymbol)
                    ->visible(fn (MessengerPageRegistry $record) => $record->is_active
                        && static::canMessengerPermission('messenger.manage_pages', 'messenger.platform.manage_all_pages'))
                    ->requiresConfirmation()
                    ->action(function (MessengerPageRegistry $record, SyncMessengerPageStatusAction $action): void {
                        try {
                            $action->execute($record, false, MessengerPageStatus::Disabled);
                            Notification::make()->title(__('dashboard.messenger_disable'))->success()->send();
                        } catch (\Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('enable')
                    ->label(__('dashboard.messenger_enable'))
                    ->icon(Heroicon::CheckCircle)
                    ->visible(fn (MessengerPageRegistry $record) => ! $record->is_active
                        && static::canMessengerPermission('messenger.manage_pages', 'messenger.platform.manage_all_pages'))
                    ->action(function (MessengerPageRegistry $record, SyncMessengerPageStatusAction $action): void {
                        try {
                            $action->execute($record, true, MessengerPageStatus::Active);
                            Notification::make()->title(__('dashboard.messenger_enable'))->success()->send();
                        } catch (\Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessengerPages::route('/'),
        ];
    }
}
