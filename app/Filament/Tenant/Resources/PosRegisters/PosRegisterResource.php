<?php

namespace App\Filament\Tenant\Resources\PosRegisters;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Tenant\Resources\PosRegisters\Pages\CreatePosRegister;
use App\Filament\Tenant\Resources\PosRegisters\Pages\EditPosRegister;
use App\Filament\Tenant\Resources\PosRegisters\Pages\ListPosRegisters;
use App\Filament\Tenant\Resources\PosRegisters\Schemas\PosRegisterForm;
use App\Filament\Tenant\Resources\PosRegisters\Tables\PosRegistersTable;
use App\Models\Tenant\PosRegister;
use App\Support\Modules\TenantModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PosRegisterResource extends Resource
{
    use RequiresTenantModule;

    protected static ?string $model = PosRegister::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ComputerDesktop;

    protected static ?int $navigationSort = 400;

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
        return __('commerce.resources.pos_registers');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commerce.resources.pos_registers');
    }

    public static function getModelLabel(): string
    {
        return __('commerce.resources.pos_register');
    }

    public static function form(Schema $schema): Schema
    {
        return PosRegisterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosRegistersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosRegisters::route('/'),
            'create' => CreatePosRegister::route('/create'),
            'edit' => EditPosRegister::route('/{record}/edit'),
        ];
    }
}
