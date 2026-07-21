<?php

namespace App\Http\Controllers\Api\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\Auth\RegisterRequest;
use App\Http\Requests\Api\Tenant\Auth\ResendCodeRequest;
use App\Http\Requests\Api\Tenant\Auth\VerifyAccountRequest;
use App\Http\Resources\Tenant\UserResource;
use App\Mail\VerifyAccountCodeMail;
use App\Models\Tenant\Customer;
use App\Models\TenantUser;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $code = (string) random_int(100000, 999999);
        $minutes = 10;

        $user = TenantUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'verification_code' => $code,
            'verification_code_expires_at' => Carbon::now()->addMinutes($minutes),
            'is_verified' => false,
            'is_admin' => false,
        ]);

        try {
            Mail::to($user->email)->send(new VerifyAccountCodeMail($code, $minutes));
        } catch (\Throwable $e) {
            // Email failed but user was created — still return success
        }

        return $this->createdResponse(null, __('auth.verification_code_sent'));
    }

    public function verifyAccount(VerifyAccountRequest $request)
    {
        $validated = $request->validated();

        $user = TenantUser::firstWhere('email', $validated['email']);
        if (! $user) {
            return $this->errorResponse(__('auth.user_not_found'), 404);
        }

        if ($user->is_verified) {
            return $this->errorResponse(__('auth.already_verified'), 400);
        }

        if (
            $user->verification_code !== $validated['code'] ||
            now()->greaterThan($user->verification_code_expires_at)
        ) {
            return $this->errorResponse(__('auth.invalid_or_expired_code'), 400);
        }

        $user->update([
            'is_verified' => true,
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        // Link customer with matching email, or create a new one
        $customer = Customer::whereHas('contacts', function ($q) use ($user) {
            $q->where('type', 'email')->where('value', $user->email);
        })->first();

        if ($customer) {
            if (! $customer->user_id) {
                $customer->update(['user_id' => $user->id]);
            }
        } else {
            DB::transaction(function () use ($user) {
                $customer = Customer::create([
                    'name' => $user->name,
                    'user_id' => $user->id,
                ]);

                $contacts = [
                    ['type' => 'email', 'value' => $user->email, 'is_primary' => true],
                ];

                if ($user->phone) {
                    $contacts[] = ['type' => 'phone', 'value' => $user->phone, 'is_primary' => true];
                    $contacts[] = ['type' => 'whatsapp', 'value' => $user->phone, 'is_primary' => true];
                }

                foreach ($contacts as $contact) {
                    $customer->contacts()->create($contact);
                }
            });
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], __('auth.account_verified'));
    }

    public function resendCode(ResendCodeRequest $request)
    {
        $validated = $request->validated();

        $user = TenantUser::firstWhere('email', $validated['email']);
        if (! $user) {
            return $this->errorResponse(__('auth.user_not_found'), 404);
        }

        if ($user->is_verified) {
            return $this->errorResponse(__('auth.already_verified'), 400);
        }

        $code = (string) random_int(100000, 999999);
        $minutes = 10;

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => Carbon::now()->addMinutes($minutes),
        ]);

        try {
            Mail::to($user->email)->send(new VerifyAccountCodeMail($code, $minutes));
        } catch (\Throwable $e) {
            return $this->errorResponse(__('auth.failed_to_send_email'), 500);
        }

        return $this->successResponse(['expires_in' => $minutes.' minutes'], __('auth.verification_code_sent'));
    }
}
