<?php

namespace App\Filament\Tenant\Resources\WhatsAppApiRequests;

use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Tables\WhatsAppApiRequestsTable;
use App\Filament\Tenant\Resources\WhatsAppApiRequests\Pages\ListWhatsAppApiRequests;
use App\Filament\Tenant\Resources\WhatsAppApiRequests\Pages\ViewWhatsAppApiRequest;
use App\Models\Tenant\WhatsAppApiRequest;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WhatsAppApiRequestResource extends Resource
{
    use ChecksWhatsAppPermissions;

    protected static ?string $model = WhatsAppApiRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpCircle;

    protected static ?int $navigationSort = 45;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_api_requests');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.whatsapp_api_requests');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.whatsapp_api_request');
    }

    public static function canViewAny(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_inbox');
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
        return WhatsAppApiRequestsTable::configure($table)
            ->filters([
                SelectFilter::make('outcome')
                    ->label(__('dashboard.whatsapp_api_outcome'))
                    ->options([
                        'success' => __('dashboard.whatsapp_api_outcome_success'),
                        'failed' => __('dashboard.whatsapp_api_outcome_failed'),
                    ]),
                SelectFilter::make('operation')
                    ->label(__('dashboard.whatsapp_api_operation'))
                    ->options([
                        'send_text' => __('dashboard.whatsapp_api_op_send_text'),
                        'send_template' => __('dashboard.whatsapp_api_op_send_template'),
                        'health_check' => __('dashboard.whatsapp_api_op_health_check'),
                        'list_templates' => __('dashboard.whatsapp_api_op_list_templates'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppApiRequests::route('/'),
            'view' => ViewWhatsAppApiRequest::route('/{record}'),
        ];
    }
}
