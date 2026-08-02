<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('currency_id')
                ->nullable()
                ->after('price')
                ->constrained('currencies')
                ->nullOnDelete();
        });

        DB::table('plans')->orderBy('id')->each(function ($plan) {
            if (empty($plan->currency)) {
                return;
            }

            $currency = DB::table('currencies')->where('code', $plan->currency)->first();

            if ($currency) {
                DB::table('plans')->where('id', $plan->id)->update(['currency_id' => $currency->id]);
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->char('currency', 3)
                ->default('SAR')
                ->after('price');
        });

        DB::table('plans')->orderBy('id')->each(function ($plan) {
            if (! $plan->currency_id) {
                return;
            }

            $currency = DB::table('currencies')->find($plan->currency_id);

            if ($currency) {
                DB::table('plans')->where('id', $plan->id)->update(['currency' => $currency->code]);
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
        });
    }
};
