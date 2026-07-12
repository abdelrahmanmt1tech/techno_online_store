<?php

namespace App\Filament\Tenant\Resources\MessengerApiRequests;

use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Shared\Messenger\Tables\MessengerApiRequestsTable;
use App\Filament\Tenant\Resources\MessengerApiRequests\Pages\ListMessengerApiRequests;
use App\Filament\Tenant\Resources\MessengerApiRequests\Pages\ViewMessengerApiRequest;
use App\Models\Tenant\MessengerApiRequest;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MessengerApiRequestResource extends Resource
{
    use ChecksMessengerPermissions;

    protected static ?string $model = MessengerApiRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpCircle;

    protected static ?int $navigationSort = 53;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.messenger_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.messenger_api_requests');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.messenger_api_requests');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.messenger_api_request');
    }

    public static function canViewAny(): bool
    {
        return static::canMessengerPermission('messenger.view_inbox');
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
        return MessengerApiRequestsTable::configure($table)
            ->filters([
                SelectFilter::make('outcome')
                    ->label(__('dashboard.messenger_api_outcome'))
                    ->options([
                        'success' => __('dashboard.messenger_api_outcome_success'),
                        'failed' => __('dashboard.messenger_api_outcome_failed'),
                    ]),
                SelectFilter::make('operation')
                    ->label(__('dashboard.messenger_api_operation'))
                    ->options([
                        'send_text' => __('dashboard.messenger_api_op_send_text'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessengerApiRequests::route('/'),
            'view' => ViewMessengerApiRequest::route('/{record}'),
        ];
    }
}
