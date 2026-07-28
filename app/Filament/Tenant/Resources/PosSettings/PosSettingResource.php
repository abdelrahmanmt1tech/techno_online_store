<?php

namespace App\Filament\Tenant\Resources\PosSettings;

use App\Filament\Tenant\Resources\PosSettings\Pages\ManagePosSettings;
use App\Filament\Tenant\Resources\PosSettings\Schemas\PosSettingForm;
use App\Models\Tenant\PosSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class PosSettingResource extends Resource
{
    protected static ?string $model = PosSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?int $navigationSort = 403;

    public static function getNavigationGroup(): ?string
    {
        return __('commerce.nav.pos');
    }

    public static function getNavigationLabel(): string
    {
        return __('commerce.resources.pos_settings');
    }

    public static function getModelLabel(): string
    {
        return __('commerce.resources.pos_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commerce.resources.pos_settings');
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
        return PosSettingForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePosSettings::route('/'),
        ];
    }
}
