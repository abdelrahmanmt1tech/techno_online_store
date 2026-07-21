<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/countries.json');

        if (! file_exists($jsonPath)) {
            $this->command?->warn('countries.json not found, skipping currency seed.');

            return;
        }

        $countries = json_decode(file_get_contents($jsonPath), true);

        if (! is_array($countries)) {
            return;
        }

        $currencies = [];

        foreach ($countries as $data) {
            foreach ($data['currencies'] ?? [] as $code => $currencyData) {
                if (isset($currencies[$code])) {
                    continue;
                }

                $currencies[$code] = [
                    'code' => $code,
                    'name' => [
                        'ar' => $currencyData['name'] ?? $code,
                        'en' => $currencyData['name'] ?? $code,
                    ],
                    'symbol' => $currencyData['symbol'] ?? null,
                ];
            }
        }

        $sort = 0;

        foreach ($currencies as $code => $currency) {
            Currency::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $currency['name'],
                    'symbol' => $currency['symbol'],
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]
            );
        }

        $this->command?->info('Seeded ' . $sort . ' currencies.');
    }
}
