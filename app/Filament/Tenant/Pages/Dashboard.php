<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

/**
 * لوحة Tenant الرئيسية — تتطلب dashboard.view.
 * الـ Widgets تتحقق من صلاحياتها عبر canView() ولا تنفّذ Queries بدون إذن.
 */
class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        $user = Auth::guard('tenant')->user();

        return $user !== null && $user->can('dashboard.view');
    }
}
