<?php

namespace App\Filament\Tenant\Resources\PosPaymentMethods;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Tenant\Resources\PosPaymentMethods\Pages\CreatePosPaymentMethod;
use App\Filament\Tenant\Resources\PosPaymentMethods\Pages\EditPosPaymentMethod;
use App\Filament\Tenant\Resources\PosPaymentMethods\Pages\ListPosPaymentMethods;
use App\Filament\Tenant\Resources\PosPaymentMethods\Schemas\PosPaymentMethodForm;
use App\Filament\Tenant\Resources\PosPaymentMethods\Tables\PosPaymentMethodsTable;
use App\Models\Tenant\PosPaymentMethod;
use App\Support\Modules\TenantModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PosPaymentMethodResource extends Resource
{
    use RequiresTenantModule;

    protected static ?string $model = PosPaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?int $navigationSort = 401;

    protected static function requiredTenantModules(): array
    {
        return [TenantModule::Pos];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('commerce.nav.pos');
    }

    public static function getNavigationLabel(): string
    {
        return __('commerce.resources.pos_payment_methods');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commerce.resources.pos_payment_methods');
    }

    public static function getModelLabel(): string
    {
        return __('commerce.resources.pos_payment_method');
    }

    public static function form(Schema $schema): Schema
    {
        return PosPaymentMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosPaymentMethodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosPaymentMethods::route('/'),
            'create' => CreatePosPaymentMethod::route('/create'),
            'edit' => EditPosPaymentMethod::route('/{record}/edit'),
        ];
    }
}
