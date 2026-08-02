<?php

namespace Database\Seeders;

use App\Models\Tenant\FollowUpType;
use Illuminate\Database\Seeder;

class FollowUpTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            1 => ['ar' => 'مكالمة هاتفية', 'en' => 'Phone Call'],
            2 => ['ar' => 'رسالة واتساب', 'en' => 'WhatsApp Message'],
            3 => ['ar' => 'بريد إلكتروني', 'en' => 'Email'],
            4 => ['ar' => 'اجتماع', 'en' => 'Meeting'],
            5 => ['ar' => 'زيارة ميدانية', 'en' => 'Site Visit'],
        ];

        foreach ($types as $id => $name) {
            FollowUpType::updateOrCreate(
                ['id' => $id],
                ['name' => $name],
            );
        }
    }
}
