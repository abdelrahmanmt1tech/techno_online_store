<?php

namespace Database\Seeders;

use App\Enums\Crm\FollowUpStatusAction;
use App\Models\Tenant\FollowUpStatus;
use Illuminate\Database\Seeder;

class FollowUpStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            1 => [
                'name' => ['ar' => 'لم يتم التواصل', 'en' => 'Not Contacted'],
                'color' => '#9ca3af',
                'action' => FollowUpStatusAction::NONE->value,
            ],
            2 => [
                'name' => ['ar' => 'تم التواصل', 'en' => 'Contacted'],
                'color' => '#3b82f6',
                'action' => FollowUpStatusAction::NONE->value,
            ],
            3 => [
                'name' => ['ar' => 'لم يرد', 'en' => 'No Answer'],
                'color' => '#f59e0b',
                'action' => FollowUpStatusAction::SCHEDULE_NEXT->value,
            ],
            4 => [
                'name' => ['ar' => 'تحويل لمرحلة أخرى', 'en' => 'Move to Stage'],
                'color' => '#8b5cf6',
                'action' => FollowUpStatusAction::CHANGE_STAGE->value,
            ],
            5 => [
                'name' => ['ar' => 'نجاح - إغلاق', 'en' => 'Won - Close'],
                'color' => '#22c55e',
                'action' => FollowUpStatusAction::SUCCESS_CLOSE->value,
            ],
            6 => [
                'name' => ['ar' => 'فشل - إغلاق', 'en' => 'Lost - Close'],
                'color' => '#ef4444',
                'action' => FollowUpStatusAction::FAILED_CLOSE->value,
            ],
        ];

        foreach ($statuses as $id => $attributes) {
            FollowUpStatus::updateOrCreate(
                ['id' => $id],
                $attributes,
            );
        }
    }
}
