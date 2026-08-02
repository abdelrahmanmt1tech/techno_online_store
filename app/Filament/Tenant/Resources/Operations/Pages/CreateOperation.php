<?php

namespace App\Filament\Tenant\Resources\Operations\Pages;

use App\Enums\OperationType;
use App\Filament\Tenant\Resources\Operations\OperationResource;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\AccountsCenterMovement;
use App\Models\Tenant\Operation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateOperation extends CreateRecord
{
    protected static string $resource = OperationResource::class;

    protected ?array $invoiceTaxPayload = null;
    protected array $accountsCenterEntriesPayload = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->assertNoDisabledAccountTreesInEntries($data);

        // Old behavior: create Operation only.
        // New behavior: keep optional "create invoice + tax" payload,
        // then create those records after operation is successfully created.
        $this->invoiceTaxPayload = null;
        $this->accountsCenterEntriesPayload = collect($data['accounts_center_entries'] ?? [])
            ->map(function ($row): array {
                return [
                    'accounts_center_id' => (int) ($row['accounts_center_id'] ?? 0),
                    'debit' => (float) ($row['debit'] ?? 0),
                    'credit' => (float) ($row['credit'] ?? 0),
                    'notes' => isset($row['notes']) ? (string) $row['notes'] : null,
                ];
            })
            ->filter(fn (array $row): bool => $row['accounts_center_id'] > 0 && ($row['debit'] > 0 || $row['credit'] > 0))
            ->values()
            ->all();

        if ((bool) ($data['link_with_invoice_tax'] ?? false) === true) {
            $this->invoiceTaxPayload = [
                'invoice_type' => (string) ($data['operation_invoice_type'] ?? 'purchase'),
                'tax_direction' => (string) ($data['invoice_tax_direction'] ?? 'purchase_tax'),
                'tax_type_id' => (int) ($data['invoice_tax_type_id'] ?? 0),
                'taxable_amount' => (float) ($data['invoice_taxable_amount'] ?? 0),
                'tax_rate' => (float) ($data['invoice_tax_rate'] ?? 0),
                'tax_value' => (float) ($data['invoice_tax_value'] ?? 0),
                'total_with_tax' => (float) ($data['invoice_total_with_tax'] ?? 0),
                'notes' => $data['operation_invoice_notes'] ?? null,
            ];
        }

        unset(
            $data['link_with_invoice_tax'],
            $data['operation_invoice_type'],
            $data['invoice_tax_direction'],
            $data['invoice_tax_type_id'],
            $data['invoice_taxable_amount'],
            $data['invoice_tax_rate'],
            $data['invoice_tax_value'],
            $data['invoice_total_with_tax'],
            $data['operation_invoice_notes'],
            $data['accounts_center_entries'],
        );

        $data['operation_type'] = ((bool) ($data['settlement'] ?? false) === true)
            ? OperationType::ADJUSTMENT
            : OperationType::NORMAL;

        return $data;
    }

    protected function assertNoDisabledAccountTreesInEntries(array $data): void
    {
        $ids = collect(array_merge($data['debitEntries'] ?? [], $data['creditEntries'] ?? []))
            ->pluck('account_tree_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $disabledCount = AccountTree::query()
            ->whereIn('id', $ids->all())
            ->where('is_disabled', true)
            ->count();

        if ($disabledCount > 0) {
            throw ValidationException::withMessages([
                'debitEntries' => __('dashboard.resources.operation.disabled_account_not_allowed'),
            ]);
        }
    }

    protected function afterCreate(): void
    {
        if ($this->record instanceof Operation) {
            $this->syncAccountsCenterMovements($this->record);
        }

        // Invoice / AccountTax / ZATCA / AccountStatement not ported — journals only.
    }

    protected function syncAccountsCenterMovements(Operation $operation): void
    {
        $rows = collect($this->accountsCenterEntriesPayload)
            ->values();

        AccountsCenterMovement::query()
            ->where('operation_id', $operation->id)
            ->where('movement_type', 'manual_operation')
            ->delete();

        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows as $row) {
            $debit = round((float) ($row['debit'] ?? 0), 2);
            $credit = round((float) ($row['credit'] ?? 0), 2);

            AccountsCenterMovement::create([
                'accounts_center_id' => (int) $row['accounts_center_id'],
                'ticket_id' => null,
                'reservation_id' => null,
                'operation_id' => $operation->id,
                'linkable_type' => $operation->linkable_type,
                'linkable_id' => $operation->linkable_id,
                'amount' => round($debit - $credit, 2),
                'debit' => $debit,
                'credit' => $credit,
                'movement_date' => $operation->date,
                'movement_type' => 'manual_operation',
                'notes' => $row['notes'] ?: __('dashboard.resources.operation.manual_accounts_center_movement_note'),
            ]);
        }
    }
}
