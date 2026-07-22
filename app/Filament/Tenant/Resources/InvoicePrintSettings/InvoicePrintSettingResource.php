<?php

namespace App\Filament\Tenant\Resources\InvoicePrintSettings;

use App\Filament\Tenant\Resources\InvoicePrintSettings\Pages\ManageInvoicePrintSettings;
use App\Filament\Tenant\Resources\InvoicePrintSettings\Schemas\InvoicePrintSettingForm;
use App\Models\Tenant\InvoicePrintSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class InvoicePrintSettingResource extends Resource
{
    protected static ?string $model = InvoicePrintSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 340;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.invoice_print_settings');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.invoice_print_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.invoice_print_settings');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return InvoicePrintSettingForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInvoicePrintSettings::route('/'),
        ];
    }
}
