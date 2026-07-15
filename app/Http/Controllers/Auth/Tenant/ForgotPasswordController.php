<?php

namespace App\Http\Controllers\Auth\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtp;
use App\Models\PasswordOtp;
use App\Models\Tenant;
use App\Models\TenantUserCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $credential = TenantUserCredential::where('email', $request->email)->first();

        if (! $credential) {
            return back()->withErrors(['email' => __('dashboard.forgot_password_email_not_found')]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordOtp::where('email', $request->email)->update(['used' => true]);

        PasswordOtp::create([
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($request->email)->send(new PasswordResetOtp($otp));

        return redirect()->route('tenant.forgot-password.verify-form')
            ->with('email', $request->email)
            ->with('success', __('dashboard.forgot_password_otp_sent'));
    }

    public function showVerifyForm()
    {
        $email = session('email');

        if (! $email) {
            return redirect()->route('tenant.forgot-password.form');
        }

        return view('auth.verify-otp', ['email' => $email]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $otpRecord = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otpRecord) {
            return back()->withErrors(['otp' => __('dashboard.forgot_password_otp_invalid')]);
        }

        $token = hash('sha256', Str::random(60));

        session(['reset_token' => $token, 'reset_email' => $request->email]);

        $otpRecord->update(['used' => true]);

        return redirect()->route('tenant.forgot-password.reset-form')
            ->with('token', $token)
            ->with('email', $request->email);
    }

    public function showResetForm()
    {
        $email = session('reset_email');
        $token = session('reset_token');

        if (! $email || ! $token) {
            return redirect()->route('tenant.forgot-password.form');
        }

        return view('auth.reset-password', ['email' => $email, 'token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = session('reset_email');
        $token = session('reset_token');

        if ($request->email !== $email || $request->token !== $token) {
            return back()->withErrors(['password' => __('dashboard.forgot_password_reset_failed')]);
        }

        $credential = TenantUserCredential::where('email', $email)->first();

        if (! $credential) {
            return back()->withErrors(['email' => __('dashboard.forgot_password_email_not_found')]);
        }

        $tenant = Tenant::find($credential->tenant_id);

        if (! $tenant) {
            return back()->withErrors(['email' => __('dashboard.forgot_password_email_not_found')]);
        }

        tenancy()->initialize($tenant);

        $user = DB::connection('tenant')->table('users')->where('email', $email)->first();

        if (! $user) {
            tenancy()->end();

            return back()->withErrors(['email' => __('dashboard.forgot_password_email_not_found')]);
        }

        DB::connection('tenant')->table('users')
            ->where('email', $email)
            ->update(['password' => Hash::make($request->password)]);

        tenancy()->end();

        session()->forget(['reset_token', 'reset_email']);

        return redirect()->route('tenant-login.form')
            ->with('success', __('dashboard.forgot_password_reset_success'));
    }
}
