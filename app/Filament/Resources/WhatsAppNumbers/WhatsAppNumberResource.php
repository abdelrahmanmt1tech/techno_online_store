<?php

namespace App\Filament\Resources\WhatsAppNumbers;

use App\Filament\Resources\WhatsAppNumbers\Pages\ListWhatsAppNumbers;
use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Tables\WhatsAppNumbersTable;
use App\Models\WhatsAppNumberRegistry;
use App\WhatsApp\Actions\SyncWhatsAppNumberStatusAction;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WhatsAppNumberResource extends Resource
{
    use ChecksWhatsAppPermissions;

    protected static ?string $model = WhatsAppNumberRegistry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Phone;

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_numbers');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.whatsapp_numbers');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.whatsapp_number');
    }

    public static function canViewAny(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_numbers', 'whatsapp.platform.view_all_numbers');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWhatsAppPermission('whatsapp.manage_numbers', 'whatsapp.platform.manage_all_numbers');
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
        return WhatsAppNumbersTable::configure($table, showTenant: true)
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label(__('dashboard.whatsapp_tenant'))
                    ->relationship('tenant', 'name'),
                SelectFilter::make('status')
                    ->label(__('dashboard.whatsapp_connection_status'))
                    ->options(collect(WhatsAppConnectionStatus::cases())->mapWithKeys(
                        fn (WhatsAppConnectionStatus $status) => [$status->value => $status->value]
                    )),
            ])
            ->recordActions([
                Action::make('disable')
                    ->label(__('dashboard.whatsapp_disable'))
                    ->icon(Heroicon::NoSymbol)
                    ->visible(fn () => static::canWhatsAppPermission('whatsapp.manage_numbers', 'whatsapp.platform.manage_all_numbers'))
                    ->requiresConfirmation()
                    ->action(function (WhatsAppNumberRegistry $record, SyncWhatsAppNumberStatusAction $action): void {
                        try {
                            $action->execute($record, false, WhatsAppConnectionStatus::Disabled);
                            Notification::make()->title(__('dashboard.whatsapp_disable'))->success()->send();
                        } catch (\Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('enable')
                    ->label(__('dashboard.whatsapp_enable'))
                    ->icon(Heroicon::CheckCircle)
                    ->visible(fn (WhatsAppNumberRegistry $record) => ! $record->is_active && static::canWhatsAppPermission('whatsapp.manage_numbers', 'whatsapp.platform.manage_all_numbers'))
                    ->action(function (WhatsAppNumberRegistry $record, SyncWhatsAppNumberStatusAction $action): void {
                        try {
                            $action->execute($record, true, WhatsAppConnectionStatus::Active);
                            Notification::make()->title(__('dashboard.whatsapp_enable'))->success()->send();
                        } catch (\Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppNumbers::route('/'),
        ];
    }
}
