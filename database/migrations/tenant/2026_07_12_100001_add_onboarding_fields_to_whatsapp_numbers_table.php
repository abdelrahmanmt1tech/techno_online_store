<?php

use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Enums\WhatsAppTokenSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->string('connection_method')->default(WhatsAppConnectionMethod::ManualApiOnly->value)->after('token_type');
            $table->string('onboarding_status')->default(WhatsAppOnboardingStatus::NotStarted->value)->after('connection_method');
            $table->boolean('coexistence_enabled')->default(false)->after('onboarding_status');
            $table->string('business_app_number')->nullable()->after('coexistence_enabled');
            $table->string('token_source')->default(WhatsAppTokenSource::Manual->value)->after('business_app_number');
            $table->text('last_onboarding_error')->nullable()->after('last_error_message');
            $table->timestamp('connected_at')->nullable()->after('last_health_check_at');
            $table->timestamp('disconnected_at')->nullable()->after('connected_at');
            $table->timestamp('reconnect_required_at')->nullable()->after('disconnected_at');
        });

        $now = now();

        DB::table('whatsapp_numbers')->orderBy('id')->chunkById(100, function ($numbers) use ($now): void {
            foreach ($numbers as $number) {
                $tokenSource = match ((string) ($number->token_type ?? 'manual')) {
                    'manual' => WhatsAppTokenSource::Manual->value,
                    'embedded_signup' => WhatsAppTokenSource::EmbeddedSignup->value,
                    'system_user' => WhatsAppTokenSource::SystemUser->value,
                    default => WhatsAppTokenSource::Manual->value,
                };

                $isActive = (bool) ($number->is_active ?? true);
                $status = (string) ($number->status ?? WhatsAppConnectionStatus::Active->value);

                $onboardingStatus = $isActive
                    ? WhatsAppOnboardingStatus::Completed->value
                    : WhatsAppOnboardingStatus::Disconnected->value;

                DB::table('whatsapp_numbers')->where('id', $number->id)->update([
                    'connection_method' => WhatsAppConnectionMethod::ManualApiOnly->value,
                    'token_source' => $tokenSource,
                    'onboarding_status' => $onboardingStatus,
                    'coexistence_enabled' => false,
                    'connected_at' => $number->created_at ?? $now,
                    'disconnected_at' => $isActive ? null : ($number->updated_at ?? $now),
                    'reconnect_required_at' => $status === WhatsAppConnectionStatus::ReconnectRequired->value
                        ? ($number->updated_at ?? $now)
                        : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->dropColumn([
                'connection_method',
                'onboarding_status',
                'coexistence_enabled',
                'business_app_number',
                'token_source',
                'last_onboarding_error',
                'connected_at',
                'disconnected_at',
                'reconnect_required_at',
            ]);
        });
    }
};
