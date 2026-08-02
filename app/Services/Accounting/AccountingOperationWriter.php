<?php

namespace App\Services\Accounting;

use App\Enums\OperationType;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Entry;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Operation;
use App\Models\TenantUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccountingOperationWriter
{
    public function __construct(
        protected SyncOperationMetadataService $operationMetadata,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function createOperationWithEntries(array $attributes, array $entries): Operation
    {

        $this->assertEntriesBalanced($entries);

        return Operation::withoutFinancialPeriodGuard(function () use ($attributes, $entries): Operation {
            return Entry::withoutFinancialPeriodGuard(function () use ($attributes, $entries): Operation {
                $operation = Operation::query()->create($attributes);

                foreach ($entries as $entry) {
                    $account = AccountTree::query()->findOrFail($entry['account_tree_id']);
                    if (! $account->isPostable()) {
                        throw ValidationException::withMessages(['account_tree_id' => __('dashboard.financial_periods.messages.parent_account_not_allowed', [
                                'account' => $account->account_name,
                            ])]);
                    }

                   Entry::query()->create([
                        'operation_id' => $operation->id,
                        'account_tree_id' => $account->id,
                        'debit' => $entry['debit'] ?? null,
                        'credit' => $entry['credit'] ?? null,
                        'notes' => $entry['notes'] ?? null,
                        'day_date' => $entry['day_date'] ?? $attributes['date'] ?? null,
                        'linkable_type' => $entry['linkable_type'] ?? ($attributes['linkable_type'] ?? null),
                        'linkable_id' => $entry['linkable_id'] ?? ($attributes['linkable_id'] ?? null),
                        'entry_type' => $entry['entry_type'] ?? null,
                        'is_locked' => $entry['is_locked'] ?? ($attributes['is_locked'] ?? false),
                        'branch_id' => $entry['branch_id'] ?? null,
                    ]);

                }

                $this->operationMetadata->refreshOperation($operation);
                return $operation->fresh(['entries']);
            });
        });
    }

    public function reverseOperation(
        Operation $source,
        ?FinancialPeriod $financialPeriod = null,
        ?TenantUser $user = null,
        ?string $notes = null,
        ?string $referenceNo = null,
    ): Operation {
        $user ??= Auth::user();
        $financialPeriod ??= $source->financialPeriod;

        $entries = $source->entries()
            ->get()
            ->map(function (Entry $entry) use ($notes): array {
                return [
                    'account_tree_id' => $entry->account_tree_id,
                    'debit' => $entry->credit ? (float) $entry->credit : null,
                    'credit' => $entry->debit ? (float) $entry->debit : null,
                    'notes' => $notes ?? __('dashboard.financial_periods.messages.reversal_entry_notes', [
                        'operation' => $entry->operation_id,
                    ]),
                    'day_date' => $entry->day_date,
                    'linkable_type' => $entry->linkable_type,
                    'linkable_id' => $entry->linkable_id,
                    'entry_type' => $entry->entry_type,
                    'branch_id' => $entry->branch_id,
                ];
            })
            ->all();

        return $this->createOperationWithEntries([
            'financial_period_id' => $financialPeriod?->id,
            'date' => $source->date,
            'comment' => $notes ?? __('dashboard.financial_periods.messages.reversal_comment', [
                'operation' => $source->id,
            ]),
            'reference_no' => $referenceNo,
            'settlement' => $source->settlement,
            'status' => $source->status,
            'operation_type' => OperationType::REVERSAL,
            'is_posted' => true,
            'posted_at' => Carbon::now(),
            'posted_by' => $user?->id,
            'is_locked' => true,
            'locked_at' => Carbon::now(),
            'locked_by' => $user?->id,
            'is_system_generated' => true,
            'linkable_type' => $source->linkable_type,
            'linkable_id' => $source->linkable_id,
            'service_type' => $source->service_type,
            'service_id' => $source->service_id,
            'source_operation_id' => $source->id,
        ], $entries);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function assertEntriesBalanced(array $entries): void
    {
        if ($entries === []) {
            throw ValidationException::withMessages(['entries' => __('dashboard.financial_periods.messages.entries_required')]);
        }

        $debit = round((float) collect($entries)->sum(fn (array $row): float => (float) ($row['debit'] ?? 0)), 2);
        $credit = round((float) collect($entries)->sum(fn (array $row): float => (float) ($row['credit'] ?? 0)), 2);

//        if ($debit <= 0 || $credit <= 0 || $debit !== $credit) { todo26
//            dd([
//                'entries' => __('dashboard.financial_periods.messages.entries_unbalanced'),
//            ]);
//        }
    }
}
