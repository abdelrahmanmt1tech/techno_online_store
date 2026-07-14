<x-layouts.auth
    :pageTitle="__('dashboard.forgot_password_verify_otp')"
    :heading="__('dashboard.forgot_password_verify_otp')">

    <p style="text-align: center; color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem;">
        {{ __('dashboard.forgot_password_verify_otp_description') }}
        <strong>{{ $email }}</strong>
    </p>

    <form x-data="{ submitting: false }" @submit="submitting = true" method="POST"
        action="{{ route('tenant.forgot-password.verify') }}" class="fi-form mt-8 space-y-6">
        @csrf

        <input type="hidden" name="email" value="{{ $email }}">

        <div class="fi-fo-field">
            <div class="fi-fo-field-label-col">
                <label for="otp" class="fi-fo-field-label">
                    <span class="fi-fo-field-label-content">
                        {{ __('dashboard.forgot_password_otp_label') }}
                    </span>
                </label>
            </div>
            <div class="fi-fo-field-content-col">
                <div class="fi-fo-field-content">
                    <x-filament::input.wrapper :invalid="$errors->has('otp')">
                        <x-filament::input type="text" name="otp" id="otp"
                            maxlength="6" pattern="[A-Za-z0-9]{6}"
                            style="text-align: center; font-size: 1.25rem; letter-spacing: 8px; font-weight: 600;"
                            required autofocus autocomplete="one-time-code"
                            placeholder="------" />
                    </x-filament::input.wrapper>

                    @error('otp')
                        <p class="fi-fo-field-wrp-error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="fi-form-actions">
            <x-filament::button type="submit" color="primary" size="md" class="w-full"
                x-bind:disabled="submitting"
                x-bind:class="{ 'opacity-50 cursor-not-allowed': submitting }">
                <span x-show="!submitting">
                    {{ __('dashboard.forgot_password_verify_otp_button') }}
                </span>
                <span x-show="submitting">
                    {{ __('dashboard.forgot_password_verifying') }}
                </span>
            </x-filament::button>
        </div>

        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="{{ route('tenant.forgot-password.form') }}" style="font-size: 0.875rem; color: #166534; text-decoration: none; font-weight: 500;">
                {{ __('dashboard.forgot_password_resend_otp') }}
            </a>
        </div>
    </form>
</x-layouts.auth>
