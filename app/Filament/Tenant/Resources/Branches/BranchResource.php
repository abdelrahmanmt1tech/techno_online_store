<?php

namespace App\Filament\Tenant\Resources\Branches;

use App\Filament\Tenant\Resources\Branches\Pages\CreateBranch;
use App\Filament\Tenant\Resources\Branches\Pages\EditBranch;
use App\Filament\Tenant\Resources\Branches\Pages\ListBranches;
use App\Filament\Tenant\Resources\Branches\Schemas\BranchForm;
use App\Filament\Tenant\Resources\Branches\Tables\BranchesTable;
use App\Models\Tenant\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;

    protected static ?int $navigationSort = 300;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.branches');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.branches');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.branch');
    }

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
