<?php

namespace App\Enums\Crm;

enum FollowUpStatusAction: string
{
    case NONE = 'none';
    case SUCCESS_CLOSE = 'success_close';
    case FAILED_CLOSE = 'failed_close';
    case CHANGE_STAGE = 'change_stage';
    case SCHEDULE_NEXT = 'schedule_next';

    public function label(): string
    {
        return __('crm.follow_up_actions.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::NONE => 'gray',
            self::SUCCESS_CLOSE => 'success',
            self::FAILED_CLOSE => 'danger',
            self::CHANGE_STAGE => 'warning',
            self::SCHEDULE_NEXT => 'info',
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
