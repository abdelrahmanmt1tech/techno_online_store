<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_print_settings', function (Blueprint $table) {
            $table->id();

            // بيانات المنشأة (نصوص قابلة للترجمة عبر Spatie JSON حيث ينطبق)
            $table->json('company_name')->nullable();
            $table->json('legal_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('commercial_register')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->json('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('extra_registration')->nullable();

            // هوية بصرية
            $table->string('primary_color', 16)->default('#065f46');
            $table->unsignedSmallInteger('logo_width')->default(140);
            $table->string('paper_size', 8)->default('A4');
            $table->string('paper_orientation', 16)->default('portrait');
            $table->string('default_locale', 16)->default('auto');
            $table->string('logo_align', 16)->default('start');

            // نصوص
            $table->json('sales_invoice_title')->nullable();
            $table->json('purchase_invoice_title')->nullable();
            $table->json('header_text')->nullable();
            $table->json('closing_note')->nullable();
            $table->json('terms')->nullable();
            $table->json('footer_text')->nullable();
            $table->json('authority_name')->nullable();
            $table->json('signature_label')->nullable();
            $table->json('stamp_label')->nullable();

            $table->string('stamp_path')->nullable();
            $table->string('signature_path')->nullable();

            $table->json('display_options')->nullable();
            $table->json('layout_options')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_print_settings');
    }
};
