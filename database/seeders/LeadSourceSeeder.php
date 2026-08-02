<?php

namespace Database\Seeders;

use App\Models\Tenant\LeadSource;
use Illuminate\Database\Seeder;

class LeadSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            1 => ['ar' => 'وسائل التواصل الاجتماعي', 'en' => 'Social Media'],
            2 => ['ar' => 'الإعلانات', 'en' => 'Advertisements'],
            3 => ['ar' => 'الموقع الإلكتروني', 'en' => 'Website'],
            4 => ['ar' => 'إحالة عميل', 'en' => 'Referral'],
            5 => ['ar' => 'اتصال مباشر', 'en' => 'Direct Contact'],
        ];

        foreach ($sources as $id => $name) {
            LeadSource::updateOrCreate(
                ['id' => $id],
                ['name' => $name],
            );
        }
    }
}
