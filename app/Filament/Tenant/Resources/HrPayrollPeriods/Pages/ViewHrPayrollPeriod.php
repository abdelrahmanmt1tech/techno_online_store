<?php

namespace App\Filament\Tenant\Resources\HrPayrollPeriods\Pages;

use App\Actions\Hr\PayrollApprovalAction;
use App\Actions\Hr\PayrollPaymentAction;
use App\Enums\Hr\PayrollPeriodStatus;
use App\Filament\Tenant\Resources\HrPayrollPeriods\HrPayrollPeriodResource;
use App\Models\Tenant\HrPayrollPeriod;
use App\Services\Hr\PayrollGenerationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ViewHrPayrollPeriod extends ViewRecord
{
    protected static string $resource = HrPayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        /** @var HrPayrollPeriod $record */
        $record = $this->record;

        return [
            Action::make('generate')
                ->label(fn () => $record->status === PayrollPeriodStatus::Generated
                    ? __('hr.actions.regenerate')
                    : __('hr.actions.generate'))
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn () => Auth::user()->can('hr.payroll.generate')
                    && in_array($record->status, [PayrollPeriodStatus::Draft, PayrollPeriodStatus::Generated], true))
                ->action(function () {
                    try {
                        /** @var HrPayrollPeriod $record */
                        $record = $this->record;
                        app(PayrollGenerationService::class)->generate(
                            $record,
                            rebuild: $record->status === PayrollPeriodStatus::Generated,
                        );
                        $this->record->refresh();
                        Notification::make()->title(__('hr.notifications.payroll_generated'))->success()->send();
                        $this->refreshFormData(['status', 'generated_at']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? __('hr.notifications.error'))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('approve')
                ->label(__('hr.actions.approve'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => Auth::user()->can('hr.payroll.approve')
                    && $record->status === PayrollPeriodStatus::Generated)
                ->action(function () {
                    try {
                        /** @var HrPayrollPeriod $record */
                        $record = $this->record;
                        app(PayrollApprovalAction::class)->execute($record);
                        $this->record->refresh();
                        Notification::make()->title(__('hr.notifications.payroll_approved'))->success()->send();
                        $this->refreshFormData(['status', 'approved_at']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? __('hr.notifications.error'))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('markPaid')
                ->label(__('hr.actions.mark_paid'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => Auth::user()->can('hr.payroll.mark_paid')
                    && $record->status === PayrollPeriodStatus::Approved)
                ->action(function () {
                    try {
                        /** @var HrPayrollPeriod $record */
                        $record = $this->record;
                        app(PayrollPaymentAction::class)->execute($record);
                        $this->record->refresh();
                        Notification::make()->title(__('hr.notifications.payroll_paid'))->success()->send();
                        $this->refreshFormData(['status', 'paid_at']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? __('hr.notifications.error'))
                            ->danger()
                            ->send();
                    }
                }),
            EditAction::make()
                ->visible(fn () => $record->status === PayrollPeriodStatus::Draft),
        ];
    }
}
