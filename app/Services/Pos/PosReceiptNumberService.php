<?php

namespace App\Services\Pos;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Pos\ReceiptNumberStrategy;
use App\Models\Tenant\PosReceiptSequence;
use App\Models\Tenant\PosRegister;
use App\Services\Erp\DocumentNumberService;
use Illuminate\Support\Facades\DB;

/**
 * Receipt numbers: Branch + Register + Date + Sequence (not raw IDs).
 */
final class PosReceiptNumberService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly CashierSessionService $sessions,
    ) {}

    public function next(PosRegister $register): string
    {
        $register->loadMissing('branch');
        $settings = $this->sessions->settings();
        $strategy = $settings->receipt_number_strategy ?? ReceiptNumberStrategy::BranchRegisterDate;

        return match ($strategy) {
            ReceiptNumberStrategy::Global => $this->numbers->next(
                DocumentSequenceType::Sale,
                $register->branch_id,
                $register->receipt_prefix ?: 'POS'
            ),
            ReceiptNumberStrategy::PerRegister => $this->nextPerRegister($register),
            default => $this->nextBranchRegisterDate($register),
        };
    }

    private function nextPerRegister(PosRegister $register): string
    {
        $prefix = trim((string) ($register->receipt_prefix ?: 'POS'));
        $base = $this->numbers->next(
            DocumentSequenceType::Sale,
            $register->branch_id,
            $prefix.'-R'.$register->id
        );

        return $base;
    }

    private function nextBranchRegisterDate(PosRegister $register): string
    {
        return DB::connection('tenant')->transaction(function () use ($register) {
            $date = now()->toDateString();
            $branchCode = $this->sanitizeCode($register->branch?->code ?: 'BR'.$register->branch_id);
            $registerCode = $this->sanitizeCode($register->code ?: ($register->receipt_prefix ?: 'R'.$register->id));
            $datePart = now()->format('Ymd');

            /** @var PosReceiptSequence|null $sequence */
            $sequence = PosReceiptSequence::query()
                ->where('branch_id', $register->branch_id)
                ->where('pos_register_id', $register->id)
                ->whereDate('sequence_date', $date)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = PosReceiptSequence::query()->create([
                    'branch_id' => $register->branch_id,
                    'pos_register_id' => $register->id,
                    'sequence_date' => $date,
                    'next_number' => 1,
                ]);
                $sequence = PosReceiptSequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();
            }

            $number = (int) $sequence->next_number;
            $sequence->next_number = $number + 1;
            $sequence->save();

            return sprintf(
                '%s-%s-%s-%s',
                $branchCode,
                $registerCode,
                $datePart,
                str_pad((string) $number, 4, '0', STR_PAD_LEFT)
            );
        });
    }

    private function sanitizeCode(string $code): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $code) ?: 'X');

        return substr($clean, 0, 12);
    }
}
