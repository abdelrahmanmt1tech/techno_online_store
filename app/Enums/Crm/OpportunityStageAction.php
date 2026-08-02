<?php

namespace App\Enums\Crm;

enum OpportunityStageAction: string
{
    case NONE = 'none';
    case OPEN = 'open';
    case SUCCESS_CLOSE = 'success_close';
    case FAILED_CLOSE = 'failed_close';
    case REOPEN = 'reopen';

    public function label(): string
    {
        return __('crm.stage_actions.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::NONE => 'gray',
            self::OPEN => 'info',
            self::SUCCESS_CLOSE => 'success',
            self::FAILED_CLOSE => 'danger',
            self::REOPEN => 'warning',
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
