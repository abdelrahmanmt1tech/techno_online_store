<?php

namespace App\Filament\Tenant\Resources\HrJobTitles;

use App\Filament\Tenant\Resources\HrJobTitles\Pages\CreateHrJobTitle;
use App\Filament\Tenant\Resources\HrJobTitles\Pages\EditHrJobTitle;
use App\Filament\Tenant\Resources\HrJobTitles\Pages\ListHrJobTitles;
use App\Filament\Tenant\Resources\HrJobTitles\Schemas\HrJobTitleForm;
use App\Filament\Tenant\Resources\HrJobTitles\Tables\HrJobTitlesTable;
use App\Models\Tenant\HrJobTitle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HrJobTitleResource extends Resource
{
    protected static ?string $model = HrJobTitle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Briefcase;

    protected static ?int $navigationSort = 501;

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.resources.job_titles');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.job_titles');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.job_title');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Hr) && (Auth::user()->can('hr.job_titles.manage'));
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('hr.job_titles.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('hr.job_titles.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('hr.job_titles.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return HrJobTitleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrJobTitlesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHrJobTitles::route('/'),
            'create' => CreateHrJobTitle::route('/create'),
            'edit' => EditHrJobTitle::route('/{record}/edit'),
        ];
    }
}
