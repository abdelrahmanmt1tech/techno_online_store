<?php

namespace App\Http\Controllers\Api\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\Auth\LoginRequest;
use App\Http\Resources\Tenant\UserResource;
use App\Models\TenantUser;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = TenantUser::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse(__('auth.invalid_credentials'), 401);
        }

        if (! $user->is_verified) {
            return $this->errorResponse(__('auth.email_not_verified'), 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, __('auth.logged_out'));
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->successResponse(null, __('auth.logged_out'));
    }
}
