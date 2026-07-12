<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('psid')->unique();
            $table->string('profile_name')->nullable();
            $table->text('profile_picture_url')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_contacts');
    }
};
