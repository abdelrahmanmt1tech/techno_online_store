<?php

declare(strict_types=1);

namespace App\Filament\Crm;

use App\Filament\Concerns\HasTenantFeatureAccess;
use Filament\Resources\Resource;

/**
 * Base for all CRM Filament resources — enforces plan features before permissions.
 */
abstract class CrmResource extends Resource
{
    use HasTenantFeatureAccess;

    public static function canViewAny(): bool
    {
        return static::passesTenantFeatureGate() && static::canViewAnyByPermission();
    }

    public static function canCreate(): bool
    {
        return static::passesTenantFeatureGate() && static::canCreateByPermission();
    }

    public static function canEdit($record): bool
    {
        return static::passesTenantFeatureGate() && static::canEditByPermission($record);
    }

    public static function canDelete($record): bool
    {
        return static::passesTenantFeatureGate() && static::canDeleteByPermission($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::passesTenantFeatureGate() && static::canDeleteAnyByPermission();
    }

    public static function canForceDelete($record): bool
    {
        return static::passesTenantFeatureGate() && static::canForceDeleteByPermission($record);
    }

    public static function canForceDeleteAny(): bool
    {
        return static::passesTenantFeatureGate() && static::canForceDeleteAnyByPermission();
    }

    public static function canRestore($record): bool
    {
        return static::passesTenantFeatureGate() && static::canRestoreByPermission($record);
    }

    public static function canRestoreAny(): bool
    {
        return static::passesTenantFeatureGate() && static::canRestoreAnyByPermission();
    }

    public static function canReorder(): bool
    {
        return static::passesTenantFeatureGate() && static::canReorderByPermission();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAnyByPermission(): bool
    {
        return true;
    }

    public static function canCreateByPermission(): bool
    {
        return static::canViewAnyByPermission();
    }

    public static function canEditByPermission($record): bool
    {
        return static::canViewAnyByPermission();
    }

    public static function canDeleteByPermission($record): bool
    {
        return static::canViewAnyByPermission();
    }

    public static function canDeleteAnyByPermission(): bool
    {
        return static::canViewAnyByPermission();
    }

    public static function canForceDeleteByPermission($record): bool
    {
        return static::canDeleteByPermission($record);
    }

    public static function canForceDeleteAnyByPermission(): bool
    {
        return static::canDeleteAnyByPermission();
    }

    public static function canRestoreByPermission($record): bool
    {
        return static::canViewAnyByPermission();
    }

    public static function canRestoreAnyByPermission(): bool
    {
        return static::canViewAnyByPermission();
    }

    public static function canReorderByPermission(): bool
    {
        return static::canViewAnyByPermission();
    }
}
