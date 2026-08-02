<?php

namespace App\Services\Crm\Commission;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class CommissionPaymentCycleNumberGenerator
{
    private const MAX_ATTEMPTS = 5;

    public static function generate(?\DateTimeInterface $referenceDate = null): string
    {
        $referenceDate ??= now();
        $periodKey = $referenceDate->format('Ym');

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(function () use ($periodKey): string {
                    DB::table('commission_payment_cycle_sequences')->insertOrIgnore([
                        'period_key' => $periodKey,
                        'last_number' => 0,
                    ]);

                    $sequence = DB::table('commission_payment_cycle_sequences')
                        ->where('period_key', $periodKey)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $nextNumber = (int) $sequence->last_number + 1;

                    DB::table('commission_payment_cycle_sequences')
                        ->where('period_key', $periodKey)
                        ->update(['last_number' => $nextNumber]);

                    return sprintf('CPC-%s-%04d', $periodKey, $nextNumber);
                });
            } catch (UniqueConstraintViolationException) {
                if ($attempt === self::MAX_ATTEMPTS) {
                    throw new \RuntimeException('Failed to generate a unique commission payment cycle number.');
                }
            }
        }

        throw new \RuntimeException('Failed to generate a unique commission payment cycle number.');
    }
}
