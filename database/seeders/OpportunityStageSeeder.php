<?php

namespace Database\Seeders;

use App\Enums\Crm\OpportunityStageAction;
use App\Models\Tenant\OpportunityStage;
use Illuminate\Database\Seeder;

class OpportunityStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            1 => [
                'name' => ['ar' => 'جديدة', 'en' => 'New'],
                'color' => '#9ca3af',
                'action' => OpportunityStageAction::OPEN->value,
                'is_final' => false,
                'sort_order' => 1,
            ],
            2 => [
                'name' => ['ar' => 'تم التواصل', 'en' => 'Contacted'],
                'color' => '#3b82f6',
                'action' => OpportunityStageAction::NONE->value,
                'is_final' => false,
                'sort_order' => 2,
            ],
            3 => [
                'name' => ['ar' => 'تقديم عرض', 'en' => 'Proposal Sent'],
                'color' => '#8b5cf6',
                'action' => OpportunityStageAction::NONE->value,
                'is_final' => false,
                'sort_order' => 3,
            ],
            4 => [
                'name' => ['ar' => 'تفاوض', 'en' => 'Negotiation'],
                'color' => '#f59e0b',
                'action' => OpportunityStageAction::NONE->value,
                'is_final' => false,
                'sort_order' => 4,
            ],
            5 => [
                'name' => ['ar' => 'تم البيع', 'en' => 'Won'],
                'color' => '#22c55e',
                'action' => OpportunityStageAction::SUCCESS_CLOSE->value,
                'is_final' => true,
                'sort_order' => 5,
            ],
            6 => [
                'name' => ['ar' => 'خسارة', 'en' => 'Lost'],
                'color' => '#ef4444',
                'action' => OpportunityStageAction::FAILED_CLOSE->value,
                'is_final' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($stages as $id => $attributes) {
            OpportunityStage::updateOrCreate(
                ['id' => $id],
                $attributes,
            );
        }
    }
}
