<?php

namespace App\Filament\Tenant\Resources\Operations\Pages;

use App\Enums\OperationType;
use App\Filament\Tenant\Resources\Operations\OperationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewOperation extends ViewRecord
{
    protected static string $resource = OperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                // [ADDED] منع الانتقال لتعديل القيود الافتتاحية من شاشة العرض العامة.
                ->visible(fn (): bool => (($this->record?->operation_type?->value ?? (string) $this->record?->operation_type) !== OperationType::OPENING->value))
                ->authorize(fn (): bool => (Auth::user()?->can('operations.update') ?? false)
                    && (($this->record?->operation_type?->value ?? (string) $this->record?->operation_type) !== OperationType::OPENING->value)),
            Action::make('lock')
                ->label(__('dashboard.resources.operation.lock'))
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                // [MODIFIED] إخفاء التعديل (lock/unlock) عن القيود الافتتاحية.
                ->visible(fn () => ! (bool) $this->record->is_locked
                    && (($this->record?->operation_type?->value ?? (string) $this->record?->operation_type) !== OperationType::OPENING->value))
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
                ->visible(fn () => (bool) $this->record->is_locked
                    && (($this->record?->operation_type?->value ?? (string) $this->record?->operation_type) !== OperationType::OPENING->value))
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
