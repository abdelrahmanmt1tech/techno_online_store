<?php

namespace App\Filament\Tenant\Resources\WhatsAppTemplates;

use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Schemas\WhatsAppTemplateForm;
use App\Filament\Shared\WhatsApp\Tables\WhatsAppTemplatesTable;
use App\Filament\Tenant\Resources\WhatsAppTemplates\Pages\CreateWhatsAppTemplate;
use App\Filament\Tenant\Resources\WhatsAppTemplates\Pages\EditWhatsAppTemplate;
use App\Filament\Tenant\Resources\WhatsAppTemplates\Pages\ListWhatsAppTemplates;
use App\Models\Tenant\WhatsAppTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplateResource extends Resource
{
    use ChecksWhatsAppPermissions;

    protected static ?string $model = WhatsAppTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 42;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_templates');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.whatsapp_templates');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.whatsapp_template');
    }

    public static function canViewAny(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_templates');
    }

    public static function canCreate(): bool
    {
        return static::canWhatsAppPermission('whatsapp.manage_templates');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWhatsAppPermission('whatsapp.manage_templates');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canWhatsAppPermission('whatsapp.manage_templates');
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsAppTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsAppTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppTemplates::route('/'),
            'create' => CreateWhatsAppTemplate::route('/create'),
            'edit' => EditWhatsAppTemplate::route('/{record}/edit'),
        ];
    }
}
