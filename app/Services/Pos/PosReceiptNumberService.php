<?php

namespace App\Services\Pos;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Pos\ReceiptNumberStrategy;
use App\Models\Tenant\PosRegister;
use App\Services\Erp\DocumentNumberService;

/**
 * Receipt numbering strategy for future POS Blade/Vue shell.
 * Business logic stays in Laravel — presentation will only display the number.
 */
final class PosReceiptNumberService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly CashierSessionService $sessions,
    ) {}

    public function next(PosRegister $register): string
    {
        $settings = $this->sessions->settings();
        $strategy = $settings->receipt_number_strategy ?? ReceiptNumberStrategy::PerRegister;

        if ($strategy === ReceiptNumberStrategy::Global) {
            return $this->numbers->next(DocumentSequenceType::Sale);
        }

        $prefix = $register->receipt_prefix ?: 'POS';
        $base = $this->numbers->next(DocumentSequenceType::Sale);

        return $prefix.'-'.$base;
    }
}
