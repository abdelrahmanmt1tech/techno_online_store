<?php

namespace App\Exceptions\Pos;

use RuntimeException;

final class PosRegisterGuardException extends RuntimeException
{
    public static function missingUser(): self
    {
        return new self(__('commerce.validation.pos_user_required'));
    }

    public static function inactiveRegister(): self
    {
        return new self(__('commerce.validation.pos_register_inactive'));
    }

    public static function missingDrawer(): self
    {
        return new self(__('commerce.validation.pos_drawer_required'));
    }

    public static function inactiveDrawer(): self
    {
        return new self(__('commerce.validation.pos_drawer_inactive'));
    }

    public static function sessionRequired(): self
    {
        return new self(__('commerce.validation.cashier_session_required'));
    }

    public static function sessionNotOperational(): self
    {
        return new self(__('commerce.validation.cashier_session_not_operational'));
    }

    public static function userMismatch(): self
    {
        return new self(__('commerce.validation.cashier_session_user_mismatch'));
    }

    public static function registerMismatch(): self
    {
        return new self(__('commerce.validation.cashier_session_register_mismatch'));
    }
}
