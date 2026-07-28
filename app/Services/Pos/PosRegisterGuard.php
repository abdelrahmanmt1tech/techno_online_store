<?php

namespace App\Services\Pos;

use App\Enums\Pos\CashierSessionStatus;
use App\Exceptions\Pos\PosRegisterGuardException;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\PosRegister;
use App\Models\TenantUser;
use Illuminate\Support\Facades\Auth;

/**
 * Unified POS pre-flight checks. All POS mutations must pass through this guard.
 */
final class PosRegisterGuard
{
    /**
     * @return array{user: TenantUser, register: PosRegister, session: CashierSession}
     */
    public function assertCanOperate(?PosRegister $register = null, ?CashierSession $session = null): array
    {
        /** @var TenantUser|null $user */
        $user = Auth::guard('tenant')->user();
        if (! $user) {
            throw PosRegisterGuardException::missingUser();
        }

        if ($session) {
            $session->loadMissing(['register.cashDrawer', 'register.branch']);
            $register = $session->register;
        }

        if (! $register) {
            throw PosRegisterGuardException::inactiveRegister();
        }

        $register->loadMissing(['cashDrawer', 'branch']);

        if (! $register->is_active) {
            throw PosRegisterGuardException::inactiveRegister();
        }

        if (! $register->cash_drawer_id || ! $register->cashDrawer) {
            throw PosRegisterGuardException::missingDrawer();
        }

        if (! $register->cashDrawer->is_active) {
            throw PosRegisterGuardException::inactiveDrawer();
        }

        if (! $session) {
            $session = $register->sessions()
                ->where('status', CashierSessionStatus::Opened->value)
                ->where('user_id', $user->id)
                ->latest('opened_at')
                ->first();
        }

        if (! $session) {
            throw PosRegisterGuardException::sessionRequired();
        }

        if ($session->status !== CashierSessionStatus::Opened) {
            throw PosRegisterGuardException::sessionNotOperational();
        }

        if ((int) $session->user_id !== (int) $user->id) {
            throw PosRegisterGuardException::userMismatch();
        }

        if ((int) $session->pos_register_id !== (int) $register->id) {
            throw PosRegisterGuardException::registerMismatch();
        }

        return [
            'user' => $user,
            'register' => $register,
            'session' => $session,
        ];
    }

    public function assertCanOpen(PosRegister $register): TenantUser
    {
        /** @var TenantUser|null $user */
        $user = Auth::guard('tenant')->user();
        if (! $user) {
            throw PosRegisterGuardException::missingUser();
        }

        $register->loadMissing(['cashDrawer', 'branch']);

        if (! $register->is_active) {
            throw PosRegisterGuardException::inactiveRegister();
        }

        if (! $register->cash_drawer_id || ! $register->cashDrawer) {
            throw PosRegisterGuardException::missingDrawer();
        }

        if (! $register->cashDrawer->is_active) {
            throw PosRegisterGuardException::inactiveDrawer();
        }

        return $user;
    }
}
