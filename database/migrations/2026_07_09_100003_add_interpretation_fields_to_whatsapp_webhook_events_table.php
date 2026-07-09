<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_webhook_events', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('event_type');
            $table->json('interpretation')->nullable()->after('summary');
            $table->json('original_payload')->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_webhook_events', function (Blueprint $table) {
            $table->dropColumn(['summary', 'interpretation', 'original_payload']);
        });
    }
};
