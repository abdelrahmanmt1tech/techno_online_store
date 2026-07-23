<?php

namespace App\Services\Erp;

use App\Enums\Erp\DocumentSequenceType;
use App\Models\Tenant\DocumentSequence;
use Illuminate\Support\Facades\DB;

/**
 * ترقيم آمن للمستندات: lockForUpdate ثم زيادة next_number.
 * لا تعتمد على قراءة آخر ID.
 */
final class DocumentNumberService
{
    public function next(DocumentSequenceType $type, ?int $branchId = null, ?string $prefix = null, int $padding = 6): string
    {
        return DB::connection('tenant')->transaction(function () use ($type, $branchId, $prefix, $padding) {
            $query = DocumentSequence::query()
                ->where('document_type', $type->value);

            if ($branchId === null) {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $branchId);
            }

            /** @var DocumentSequence|null $sequence */
            $sequence = $query->lockForUpdate()->first();

            if (! $sequence) {
                $sequence = DocumentSequence::query()->create([
                    'document_type' => $type->value,
                    'branch_id' => $branchId,
                    'prefix' => $prefix ?? $type->prefix(),
                    'padding' => $padding,
                    'next_number' => 1,
                ]);

                $sequence = DocumentSequence::query()
                    ->whereKey($sequence->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $number = (int) $sequence->next_number;
            $sequence->next_number = $number + 1;
            $sequence->save();

            $usedPrefix = $sequence->prefix ?: ($prefix ?? $type->prefix());
            $pad = (int) ($sequence->padding ?: $padding);

            return sprintf('%s-%s', $usedPrefix, str_pad((string) $number, $pad, '0', STR_PAD_LEFT));
        });
    }
}
