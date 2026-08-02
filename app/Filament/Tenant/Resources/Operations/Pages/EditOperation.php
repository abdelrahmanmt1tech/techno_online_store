<?php

namespace App\Filament\Tenant\Resources\Operations\Pages;

use App\Enums\OperationType;
use App\Filament\Tenant\Resources\Operations\OperationResource;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\AccountsCenterMovement;
use App\Models\Tenant\Operation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditOperation extends EditRecord
{
    protected static string $resource = OperationResource::class;

    protected ?string $referenceNoBeforeSave = null;
    protected array $accountsCenterEntriesPayload = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // [ADDED] حماية إضافية: حتى مع رابط مباشر، القيد الافتتاحي لا يعدّل من هذه الصفحة.
        if (($this->record?->operation_type?->value ?? (string) $this->record?->operation_type) === OperationType::OPENING->value) {
            abort(403);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    { //todo 26
        $this->assertNoDisabledAccountTreesInEntries($data);
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

        if (
            blank($this->record?->operation_type)
            || in_array((string) $this->record?->operation_type?->value, [
                OperationType::NORMAL->value,
                OperationType::ADJUSTMENT->value,
            ], true)
        ) {
            $data['operation_type'] = ((bool) ($data['settlement'] ?? false) === true)
                ? OperationType::ADJUSTMENT
                : OperationType::NORMAL;
        }

        unset($data['accounts_center_entries']);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $rows = AccountsCenterMovement::query()
            ->where('operation_id', $this->record->id)
            ->where('movement_type', 'manual_operation')
            ->orderBy('id')
            ->get()
            ->map(function (AccountsCenterMovement $movement): array {
                $amount = (float) ($movement->amount ?? 0);
                $debit = (float) ($movement->debit ?? 0);
                $credit = (float) ($movement->credit ?? 0);

                return [
                    'accounts_center_id' => (int) $movement->accounts_center_id,
                    'debit' => $debit > 0 ? $debit : ($amount > 0 ? $amount : 0),
                    'credit' => $credit > 0 ? $credit : ($amount < 0 ? abs($amount) : 0),
                    'notes' => $movement->notes,
                ];
            })
            ->values()
            ->all();

        $data['accounts_center_entries'] = $rows;

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

    protected function beforeSave(): void
    {
        $this->referenceNoBeforeSave = $this->record?->reference_no;
    }

    protected function afterSave(): void
    {
        if (! ($this->record instanceof Operation)) {
            return;
        }

        $legacyDocNos = [];
        if (
            filled($this->referenceNoBeforeSave)
            && (string) $this->referenceNoBeforeSave !== (string) ($this->record->reference_no ?? '')
        ) {
            $legacyDocNos[] = (string) $this->referenceNoBeforeSave;
        }

        $this->record->refresh();

        $this->syncAccountsCenterMovements($this->record);
    }

    protected function syncAccountsCenterMovements(Operation $operation): void
    {
        $rows = collect($this->accountsCenterEntriesPayload)->values();

        AccountsCenterMovement::query()
            ->where('operation_id', $operation->id)
            ->where('movement_type', 'manual_operation')
            ->delete();

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

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('operations.show') ?? false),
            DeleteAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('operations.delete') ?? false),
            ForceDeleteAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('operations.force_delete') ?? false),
            RestoreAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('operations.restore') ?? false),
            Action::make('lock')
                ->label(__('dashboard.resources.operation.lock'))
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn (): bool => ! (bool) $this->record->is_locked)
                ->authorize(fn (): bool => Auth::user()?->can('operations.lock') ?? false)
                ->action(function (): void {
                    \App\Models\Tenant\Operation::withoutFinancialPeriodGuard(function (): void {
                        $this->record->forceFill([
                            'is_locked' => true,
                            'locked_at' => now(),
                            'locked_by' => Auth::id(),
                        ])->save();
                    });
                }),
            Action::make('unlock')
                ->label(__('dashboard.resources.operation.unlock'))
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(fn (): bool => (bool) $this->record->is_locked)
                ->authorize(fn (): bool => Auth::user()?->can('operations.unlock') ?? false)
                ->action(function (): void {
                    \App\Models\Tenant\Operation::withoutFinancialPeriodGuard(function (): void {
                        $this->record->forceFill([
                            'is_locked' => false,
                            'locked_at' => null,
                            'locked_by' => null,
                        ])->save();
                    });
                }),
        ];
    }
}
