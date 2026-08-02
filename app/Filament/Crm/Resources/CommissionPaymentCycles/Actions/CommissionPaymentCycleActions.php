<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Actions;

use App\Models\Tenant\CommissionPaymentCycle;
use App\Models\TenantUser;
use App\Services\Crm\Commission\CommissionPaymentCycleWorkflowService;
use App\Support\Crm\Commission\CommissionPaymentCycleAccess;
use App\Support\Crm\Commission\CommissionPaymentCycleState;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

final class CommissionPaymentCycleActions
{
    /**
     * @return array<int, Action>
     */
    public static function headerActions(?CommissionPaymentCycle $record = null): array
    {
        return [
            self::submitAction($record),
            self::approveAction($record),
            self::executePaymentAction($record),
            self::cancelAction($record),
        ];
    }

    private static function submitAction(?CommissionPaymentCycle $record): Action
    {
        return Action::make('submit_for_approval')
            ->label(__('crm.payment_cycles.actions.submit'))
            ->icon(Heroicon::PaperAirplane)
            ->color('warning')
            ->visible(fn (): bool => self::visibleFor($record, fn (TenantUser $user, CommissionPaymentCycle $cycle): bool => CommissionPaymentCycleState::isSubmittable($cycle)
                && CommissionPaymentCycleAccess::canUpdate($user, $cycle)))
            ->requiresConfirmation()
            ->modalDescription(__('crm.payment_cycles.confirmations.submit'))
            ->action(function (CommissionPaymentCycle $cycle, CommissionPaymentCycleWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof TenantUser, 403);

                $workflow->submitForApproval($cycle, $user);

                Notification::make()
                    ->title(__('crm.payment_cycles.notifications.submitted'))
                    ->success()
                    ->send();
            });
    }

    private static function approveAction(?CommissionPaymentCycle $record): Action
    {
        return Action::make('approve')
            ->label(__('crm.payment_cycles.actions.approve'))
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(fn (): bool => self::visibleFor($record, fn (TenantUser $user, CommissionPaymentCycle $cycle): bool => CommissionPaymentCycleAccess::canApprove($user, $cycle)))
            ->requiresConfirmation()
            ->modalDescription(__('crm.payment_cycles.confirmations.approve'))
            ->action(function (CommissionPaymentCycle $cycle, CommissionPaymentCycleWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof TenantUser, 403);

                $workflow->approve($cycle, $user);

                Notification::make()
                    ->title(__('crm.payment_cycles.notifications.approved'))
                    ->success()
                    ->send();
            });
    }

    private static function executePaymentAction(?CommissionPaymentCycle $record): Action
    {
        return Action::make('execute_payments')
            ->label(__('crm.payment_cycles.actions.execute_payment'))
            ->icon(Heroicon::Banknotes)
            ->color('primary')
            ->visible(fn (): bool => self::visibleFor($record, fn (TenantUser $user, CommissionPaymentCycle $cycle): bool => CommissionPaymentCycleAccess::canExecutePayment($user, $cycle)))
            ->requiresConfirmation()
            ->modalDescription(__('crm.payment_cycles.confirmations.execute_payment'))
            ->action(function (CommissionPaymentCycle $cycle, CommissionPaymentCycleWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof TenantUser, 403);

                $payments = $workflow->executePayments($cycle, $user);

                Notification::make()
                    ->title(__('crm.payment_cycles.notifications.payments_executed'))
                    ->body(__('crm.payment_cycles.messages.payments_executed_count', [
                        'count' => $payments->count(),
                    ]))
                    ->success()
                    ->send();
            });
    }

    private static function cancelAction(?CommissionPaymentCycle $record): Action
    {
        return Action::make('cancel')
            ->label(__('crm.payment_cycles.actions.cancel'))
            ->icon(Heroicon::NoSymbol)
            ->color('gray')
            ->visible(fn (): bool => self::visibleFor($record, fn (TenantUser $user, CommissionPaymentCycle $cycle): bool => CommissionPaymentCycleAccess::canCancel($user, $cycle)))
            ->schema([
                Textarea::make('reason')
                    ->label(__('crm.payment_cycles.fields.cancellation_reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (CommissionPaymentCycle $cycle, array $data, CommissionPaymentCycleWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof TenantUser, 403);

                $workflow->cancel($cycle, $user, (string) $data['reason']);

                Notification::make()
                    ->title(__('crm.payment_cycles.notifications.cancelled'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Filament 5 header actions do not inject the page record into visible() closures.
     * Bind the record explicitly when building actions on View/Edit pages.
     *
     * @param  callable(TenantUser, CommissionPaymentCycle): bool  $check
     */
    private static function visibleFor(?CommissionPaymentCycle $record, callable $check): bool
    {
        $user = Auth::user();

        if (! $record instanceof CommissionPaymentCycle || ! $user instanceof TenantUser) {
            return false;
        }

        return $check($user, $record);
    }
}
