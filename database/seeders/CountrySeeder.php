<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/countries.json');

        if (! file_exists($jsonPath)) {
            $this->command?->warn('countries.json not found, skipping country seed.');

            return;
        }

        $countries = json_decode(file_get_contents($jsonPath), true);

        if (! is_array($countries)) {
            return;
        }

        $sort = 0;

        foreach ($countries as $data) {
            $englishName = $data['name']['common'] ?? null;
            $arabicName = $data['translations']['ara']['common'] ?? $englishName;

            $currencyCode = array_key_first($data['currencies'] ?? []);
            $currencyData = $data['currencies'][$currencyCode] ?? null;

            $phoneCode = null;
            if (! empty($data['idd']['root']) && ! empty($data['idd']['suffixes'][0])) {
                $phoneCode = $data['idd']['root'] . $data['idd']['suffixes'][0];
            }

            Country::updateOrCreate(
                ['name->en' => $englishName],
                [
                    'name' => [
                        'ar' => $arabicName,
                        'en' => $englishName,
                    ],
                    'country_code' => $data['cca2'] ?? null,
                    'currency_name' => [
                        'ar' => $currencyData['name'] ?? null,
                        'en' => $currencyData['name'] ?? null,
                    ],
                    'currency_symbol' => $currencyData['symbol'] ?? null,
                    'currency_code' => $currencyCode ?: null,
                    'phone_code' => $phoneCode,
                    'is_active' => true,
                    'sort_order' => $sort++,
                    'locale' => 'ar',
                ]
            );
        }

        $this->command?->info('Seeded ' . $sort . ' countries.');
    }
}
