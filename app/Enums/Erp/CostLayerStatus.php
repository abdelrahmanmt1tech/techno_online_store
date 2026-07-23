<?php

namespace App\Enums\Erp;

enum CostLayerStatus: string
{
    case Open = 'open';
    case Partial = 'partial';
    case Consumed = 'consumed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __('erp.cost_layer_statuses.'.$this->value);
    }
}
