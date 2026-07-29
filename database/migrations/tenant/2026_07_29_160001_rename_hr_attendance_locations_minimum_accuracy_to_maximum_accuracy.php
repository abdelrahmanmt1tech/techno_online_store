<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_attendance_locations', function (Blueprint $table) {
            $table->renameColumn('minimum_accuracy_meters', 'maximum_accuracy_meters');
        });
    }

    public function down(): void
    {
        Schema::table('hr_attendance_locations', function (Blueprint $table) {
            $table->renameColumn('maximum_accuracy_meters', 'minimum_accuracy_meters');
        });
    }
};
