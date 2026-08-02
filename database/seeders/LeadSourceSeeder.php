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
            6 => ['ar' => 'زيارة مباشرة', 'en' => 'Walk-in'],
            7 => ['ar' => 'نقطة البيع', 'en' => 'POS'],
            8 => ['ar' => 'المتاجر الإلكترونية', 'en' => 'Marketplace'],
            9 => ['ar' => 'الهاتف', 'en' => 'Phone'],
        ];

        foreach ($sources as $id => $name) {
            LeadSource::updateOrCreate(
                ['id' => $id],
                ['name' => $name],
            );
        }
    }
}
