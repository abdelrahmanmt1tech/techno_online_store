<?php

namespace App\Filament\Tenant\Resources\MessengerPages;

use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Shared\Messenger\Schemas\MessengerPageForm;
use App\Filament\Shared\Messenger\Tables\MessengerPagesTable;
use App\Filament\Tenant\Resources\MessengerPages\Pages\CreateMessengerPage;
use App\Filament\Tenant\Resources\MessengerPages\Pages\EditMessengerPage;
use App\Filament\Tenant\Resources\MessengerPages\Pages\ListMessengerPages;
use App\Messenger\Enums\MessengerPageStatus;
use App\Models\Tenant\MessengerPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MessengerPageResource extends Resource
{
    use ChecksMessengerPermissions;

    protected static ?string $model = MessengerPage::class;

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
        return static::canMessengerPermission('messenger.view_pages');
    }

    public static function canCreate(): bool
    {
        return static::canMessengerPermission('messenger.manage_pages');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canMessengerPermission('messenger.manage_pages');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canMessengerPermission('messenger.manage_pages');
    }

    public static function form(Schema $schema): Schema
    {
        return MessengerPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MessengerPagesTable::configure($table)
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => static::canMessengerPermission('messenger.manage_pages')),
                Action::make('setDefault')
                    ->label(__('dashboard.messenger_set_default'))
                    ->icon(Heroicon::Star)
                    ->visible(fn (MessengerPage $record) => static::canMessengerPermission('messenger.manage_pages') && ! $record->is_default)
                    ->action(function (MessengerPage $record): void {
                        MessengerPage::query()->update(['is_default' => false]);
                        $record->update(['is_default' => true]);
                        Notification::make()->title(__('dashboard.messenger_set_default'))->success()->send();
                    }),
                Action::make('disable')
                    ->label(__('dashboard.messenger_disable'))
                    ->icon(Heroicon::NoSymbol)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (MessengerPage $record) => static::canMessengerPermission('messenger.manage_pages')
                        && ($record->is_active || $record->status === MessengerPageStatus::Active))
                    ->action(function (MessengerPage $record): void {
                        $record->update([
                            'is_active' => false,
                            'status' => MessengerPageStatus::Disabled,
                            'disconnected_at' => now(),
                        ]);
                        Notification::make()->title(__('dashboard.messenger_disable'))->success()->send();
                    }),
                Action::make('disconnect')
                    ->label(__('dashboard.messenger_disconnect'))
                    ->icon(Heroicon::LinkSlash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('dashboard.messenger_disconnect_help'))
                    ->visible(fn () => static::canMessengerPermission('messenger.manage_pages'))
                    ->action(function (MessengerPage $record): void {
                        $record->update([
                            'is_active' => false,
                            'status' => MessengerPageStatus::Disabled,
                            'disconnected_at' => now(),
                        ]);
                        Notification::make()->title(__('dashboard.messenger_disconnect'))->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessengerPages::route('/'),
            'create' => CreateMessengerPage::route('/create'),
            'edit' => EditMessengerPage::route('/{record}/edit'),
        ];
    }
}
