<?php

namespace App\Http\Controllers\Api\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Tenant\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Tenant\Auth\VerifyResetCodeRequest;
use App\Mail\PasswordResetCodeMail;
use App\Models\TenantUser;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    use ApiResponse;

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $email = $request->validated()['email'];

        $user = TenantUser::firstWhere('email', $email);
        if (! $user) {
            return $this->errorResponse(__('auth.user_not_found'), 404);
        }

        $code = (string) random_int(100000, 999999);
        $minutes = 10;

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => Carbon::now()->addMinutes($minutes),
        ]);

        try {
            Mail::to($user->email)->send(new PasswordResetCodeMail($code, $minutes));
        } catch (\Throwable $e) {
            return $this->errorResponse(__('auth.failed_to_send_email'), 500);
        }

        return $this->successResponse(['expires_in' => $minutes.' minutes'], __('auth.verification_code_sent'));
    }

    public function verifyResetCode(VerifyResetCodeRequest $request)
    {
        $v = $request->validated();

        $user = TenantUser::firstWhere('email', $v['email']);
        if (! $user) {
            return $this->errorResponse(__('auth.user_not_found'), 404);
        }

        if (
            $user->verification_code !== $v['code'] ||
            now()->greaterThan($user->verification_code_expires_at)
        ) {
            return $this->errorResponse(__('auth.invalid_or_expired_code'), 400);
        }

        $plainToken = Str::random(64);

        $user->update([
            'reset_password_token' => hash('sha256', $plainToken),
            'reset_password_token_expires_at' => now()->addMinutes(10),
        ]);

        return $this->successResponse([
            'reset_token' => $plainToken,
            'expires_in' => 600,
        ], __('auth.success'));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $v = $request->validated();

        $user = TenantUser::firstWhere('email', $v['email']);
        if (! $user) {
            return $this->errorResponse(__('auth.user_not_found'), 404);
        }

        $invalid = ! $user->reset_password_token
            || ! $user->reset_password_token_expires_at
            || now()->greaterThan($user->reset_password_token_expires_at)
            || ! hash_equals($user->reset_password_token, hash('sha256', $v['reset_token']));

        if ($invalid) {
            return $this->errorResponse(__('auth.invalid_or_expired_code'), 400);
        }

        $user->update([
            'password' => $v['password'],
            'remember_token' => Str::random(60),
            'verification_code' => null,
            'verification_code_expires_at' => null,
            'reset_password_token' => null,
            'reset_password_token_expires_at' => null,
        ]);

        return $this->successResponse(null, __('auth.password_reset_success'));
    }
}
