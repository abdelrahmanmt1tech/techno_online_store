<?php

namespace App\Enums\Erp;

enum MovementDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return __('erp.movement_directions.'.$this->value);
    }
}
