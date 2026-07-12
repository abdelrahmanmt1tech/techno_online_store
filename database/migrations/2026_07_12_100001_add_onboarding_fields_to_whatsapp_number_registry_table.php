<?php

use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_number_registry', function (Blueprint $table) {
            $table->string('connection_method')->default(WhatsAppConnectionMethod::ManualApiOnly->value)->after('business_name');
            $table->string('onboarding_status')->default(WhatsAppOnboardingStatus::Completed->value)->after('connection_method');
            $table->boolean('coexistence_enabled')->default(false)->after('onboarding_status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_number_registry', function (Blueprint $table) {
            $table->dropColumn([
                'connection_method',
                'onboarding_status',
                'coexistence_enabled',
            ]);
        });
    }
};
