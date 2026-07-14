<x-layouts.auth
    :pageTitle="__('dashboard.forgot_password')"
    :heading="__('dashboard.forgot_password')">

    <form x-data="{ submitting: false }" @submit="submitting = true" method="POST"
        action="{{ route('tenant.forgot-password.send') }}" class="fi-form mt-8 space-y-6">
        @csrf

        <div class="fi-fo-field">
            <div class="fi-fo-field-label-col">
                <label for="email" class="fi-fo-field-label">
                    <span class="fi-fo-field-label-content">
                        {{ __('filament-panels::auth/pages/login.form.email.label') }}
                    </span>
                </label>
            </div>
            <div class="fi-fo-field-content-col">
                <div class="fi-fo-field-content">
                    <x-filament::input.wrapper :invalid="$errors->has('email')">
                        <x-filament::input type="email" name="email" id="email"
                            value="{{ old('email') }}" required autofocus autocomplete="email" />
                    </x-filament::input.wrapper>

                    @error('email')
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
                    {{ __('dashboard.forgot_password_send_otp') }}
                </span>
                <span x-show="submitting">
                    {{ __('dashboard.forgot_password_sending') }}
                </span>
            </x-filament::button>
        </div>

        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="{{ route('tenant-login.form') }}" style="font-size: 0.875rem; color: #166534; text-decoration: none; font-weight: 500;">
                {{ __('dashboard.back_to_login') }}
            </a>
        </div>
    </form>
</x-layouts.auth>
