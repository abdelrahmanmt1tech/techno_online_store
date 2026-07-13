<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TenantLoginToken;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantTokenLoginController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        if (! tenant()) {
            abort(404);
        }

        $tenantId = tenant('id');
        $hashed = hash('sha256', $token);

        $loginToken = tenancy()->central(function () use ($hashed, $tenantId) {
            return TenantLoginToken::where('token', $hashed)
                ->where('tenant_id', $tenantId)
                ->first();
        });

        if (! $loginToken || ! $loginToken->isValid()) {
            abort(404, __('auth.failed'));
        }

        tenancy()->central(function () use ($loginToken) {
            $loginToken->update(['consumed_at' => now()]);
        });

        $userClass = config('auth.providers.tenant_users.model');
        $user = $userClass::find($loginToken->user_id);

        if (! $user) {
            abort(404, __('auth.failed'));
        }

        Auth::guard('tenant')->login($user);

        $panel = Filament::getPanel('tenant');

        return redirect()->to($panel->getUrl());
    }
}
