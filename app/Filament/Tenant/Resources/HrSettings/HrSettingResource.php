<?php

namespace App\Filament\Tenant\Resources\HrSettings;

use App\Filament\Tenant\Resources\HrSettings\Pages\ManageHrSettings;
use App\Filament\Tenant\Resources\HrSettings\Schemas\HrSettingForm;
use App\Models\Tenant\HrSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HrSettingResource extends Resource
{
    protected static ?string $model = HrSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?int $navigationSort = 507;

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.resources.settings');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.settings');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('hr.settings.manage');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('hr.settings.manage');
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
        return HrSettingForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHrSettings::route('/'),
        ];
    }
}
