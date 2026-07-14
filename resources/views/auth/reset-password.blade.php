<x-layouts.auth
    :pageTitle="__('dashboard.forgot_password_reset')"
    :heading="__('dashboard.forgot_password_reset')">

    <form x-data="{ submitting: false }" @submit="submitting = true" method="POST"
        action="{{ route('tenant.forgot-password.reset') }}" class="fi-form mt-8 space-y-6">
        @csrf

        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="fi-fo-field">
            <div class="fi-fo-field-label-col">
                <label for="password" class="fi-fo-field-label">
                    <span class="fi-fo-field-label-content">
                        {{ __('filament-panels::auth/pages/login.form.password.label') }}
                    </span>
                </label>
            </div>
            <div class="fi-fo-field-content-col">
                <div class="fi-fo-field-content">
                    <x-filament::input.wrapper :invalid="$errors->has('password')">
                        <x-filament::input type="password" name="password" id="password"
                            required autofocus autocomplete="new-password" />
                    </x-filament::input.wrapper>

                    @error('password')
                        <p class="fi-fo-field-wrp-error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="fi-fo-field">
            <div class="fi-fo-field-label-col">
                <label for="password_confirmation" class="fi-fo-field-label">
                    <span class="fi-fo-field-label-content">
                        {{ __('dashboard.forgot_password_confirm_password') }}
                    </span>
                </label>
            </div>
            <div class="fi-fo-field-content-col">
                <div class="fi-fo-field-content">
                    <x-filament::input.wrapper>
                        <x-filament::input type="password" name="password_confirmation" id="password_confirmation"
                            required autocomplete="new-password" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>

        <div class="fi-form-actions">
            <x-filament::button type="submit" color="primary" size="md" class="w-full"
                x-bind:disabled="submitting"
                x-bind:class="{ 'opacity-50 cursor-not-allowed': submitting }">
                <span x-show="!submitting">
                    {{ __('dashboard.forgot_password_reset_button') }}
                </span>
                <span x-show="submitting">
                    {{ __('dashboard.forgot_password_resetting') }}
                </span>
            </x-filament::button>
        </div>
    </form>
</x-layouts.auth>
