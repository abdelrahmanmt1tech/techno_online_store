<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantLoginToken;
use App\Models\TenantUserCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TenantLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('tenant-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credential = TenantUserCredential::where('email', $request->email)->first();

        if (! $credential) {
            return back()->withErrors(['email' => __('auth.failed')]);
        }

        $tenant = Tenant::find($credential->tenant_id);

        if (! $tenant) {
            return back()->withErrors(['email' => __('auth.failed')]);
        }

        tenancy()->initialize($tenant);

        $authenticated = Auth::guard('tenant')->attempt([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if (! $authenticated) {
            tenancy()->end();

            return back()->withErrors(['email' => __('auth.failed')]);
        }

        $user = Auth::guard('tenant')->user();
        $userId = $user->getAuthIdentifier();

        tenancy()->end();

        $rawToken = Str::random(60);

        TenantLoginToken::create([
            'tenant_id' => $tenant->id,
            'user_id' => $userId,
            'token' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(5),
        ]);

        $domain = $tenant->domains()->first()?->domain;

        if (! $domain) {
            return back()->withErrors(['email' => __('auth.failed')]);
        }

        $scheme = $request->getScheme();
        $redirectUrl = "{$scheme}://{$domain}/app/login/{$rawToken}";

        return redirect()->away($redirectUrl);
    }
}
