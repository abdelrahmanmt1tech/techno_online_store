<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_code')->nullable()->after('is_admin');
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            $table->boolean('is_verified')->default(false)->after('verification_code_expires_at');
            $table->string('reset_password_token')->nullable()->after('is_verified');
            $table->timestamp('reset_password_token_expires_at')->nullable()->after('reset_password_token');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code',
                'verification_code_expires_at',
                'is_verified',
                'reset_password_token',
                'reset_password_token_expires_at',
            ]);
        });
    }
};
