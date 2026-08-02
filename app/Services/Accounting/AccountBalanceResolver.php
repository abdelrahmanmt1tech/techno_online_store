<?php

namespace App\Services\Accounting;

use App\Enums\BalanceSide;
use App\Models\Tenant\AccountTree;

class AccountBalanceResolver
{
    public function normalSide(?AccountTree $accountTree): BalanceSide
    {
        return $accountTree?->normalBalanceSide() ?? BalanceSide::DEBIT;
    }

    public function signedBalance(float $debit, float $credit, ?AccountTree $accountTree = null): float
    {
        $normalSide = $this->normalSide($accountTree);

        return $normalSide === BalanceSide::DEBIT
            ? round($debit - $credit, 2)
            : round($credit - $debit, 2);
    }

    public function splitSignedBalance(float $signedBalance, ?AccountTree $accountTree = null): array
    {
        $normalSide = $this->normalSide($accountTree);
        $absolute = round(abs($signedBalance), 2);

        if ($absolute === 0.0) {
            return [
                'debit' => 0.0,
                'credit' => 0.0,
                'balance_side' => null,
                'net_balance' => 0.0,
            ];
        }

        if ($signedBalance >= 0) {
            return [
                'debit' => $normalSide === BalanceSide::DEBIT ? $absolute : 0.0,
                'credit' => $normalSide === BalanceSide::CREDIT ? $absolute : 0.0,
                'balance_side' => $normalSide,
                'net_balance' => $absolute,
            ];
        }

        $opposite = $normalSide === BalanceSide::DEBIT ? BalanceSide::CREDIT : BalanceSide::DEBIT;

        return [
            'debit' => $opposite === BalanceSide::DEBIT ? $absolute : 0.0,
            'credit' => $opposite === BalanceSide::CREDIT ? $absolute : 0.0,
            'balance_side' => $opposite,
            'net_balance' => $absolute,
        ];
    }

    public function resolveClosingBalance(float $debit, float $credit, ?AccountTree $accountTree = null): array
    {
        return $this->splitSignedBalance(
            $this->signedBalance($debit, $credit, $accountTree),
            $accountTree
        );
    }
}
