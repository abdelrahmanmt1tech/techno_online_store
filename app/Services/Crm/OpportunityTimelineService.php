<?php

namespace App\Services\Crm;

use App\Models\Tenant\Opportunity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OpportunityTimelineService
{
    public function build(Opportunity $opportunity, int $limit = 50): Collection
    {
        $events = collect();

        foreach ($opportunity->opportunityStageLogs()->with(['fromStage', 'toStage', 'changedBy'])->get() as $log) {
            $events->push([
                'type' => 'stage_change',
                'label' => __('crm.timeline.stage_change'),
                'description' => ($log->fromStage?->name ?? '-') . ' → ' . ($log->toStage?->name ?? '-'),
                'user' => $log->changedBy?->name,
                'at' => $log->created_at,
            ]);
        }

        foreach ($opportunity->opportunityAssignmentLogs()->with(['fromUser', 'toUser', 'changedBy'])->get() as $log) {
            $events->push([
                'type' => 'assignment',
                'label' => __('crm.timeline.assignment'),
                'description' => ($log->fromUser?->name ?? '-') . ' → ' . ($log->toUser?->name ?? '-'),
                'user' => $log->changedBy?->name,
                'at' => $log->created_at,
            ]);
        }

        foreach ($opportunity->notes()->with('createdBy')->get() as $note) {
            $events->push([
                'type' => 'note',
                'label' => __('crm.timeline.note'),
                'description' => \Illuminate\Support\Str::limit($note->note, 120),
                'user' => $note->createdBy?->name,
                'at' => $note->created_at,
            ]);
        }

        foreach ($opportunity->opportunityFollowUps()->with(['followUpType', 'followUpStatus'])->get() as $followUp) {
            if ($followUp->scheduled_at) {
                $events->push([
                    'type' => 'follow_up_created',
                    'label' => __('crm.timeline.follow_up_created'),
                    'description' => $followUp->followUpType?->name,
                    'user' => null,
                    'at' => Carbon::parse($followUp->scheduled_at),
                ]);
            }

            if ($followUp->completed_at) {
                $events->push([
                    'type' => 'follow_up_completed',
                    'label' => __('crm.timeline.follow_up_completed'),
                    'description' => $followUp->followUpStatus?->name,
                    'user' => null,
                    'at' => Carbon::parse($followUp->completed_at),
                ]);
            }
        }

        return $events
            ->filter(fn (array $event) => $event['at'] !== null)
            ->sortByDesc(fn (array $event) => $event['at'])
            ->take($limit)
            ->values();
    }
}
