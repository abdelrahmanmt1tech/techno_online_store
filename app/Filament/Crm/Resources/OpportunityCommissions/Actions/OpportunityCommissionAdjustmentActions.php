<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Actions;

use App\Enums\Crm\CommissionAdjustmentDirection;
use App\Enums\Crm\CommissionAdjustmentStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Models\Tenant\OpportunityCommissionAdjustment;
use App\Services\Crm\Commission\CommissionAdjustmentCalculator;
use App\Services\Crm\Commission\OpportunityCommissionAdjustmentWorkflowService;
use App\Support\Crm\Commission\OpportunityCommissionAdjustmentAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class OpportunityCommissionAdjustmentActions
{
    public static function createAction(OpportunityCommission $commission): Action
    {
        return Action::make('create_adjustment')
            ->label(__('crm.commissions.adjustments.actions.create'))
            ->icon(Heroicon::Plus)
            ->color('primary')
            ->visible(fn (): bool => Auth::user() !== null
                && OpportunityCommissionAdjustmentAccess::canCreate(Auth::user(), $commission))
            ->schema(fn (): array => self::createFormSchema($commission))
            ->action(function (array $data, OpportunityCommissionAdjustmentWorkflowService $workflow) use ($commission): void {
                $user = Auth::user();
                abort_unless($user !== null, 403);

                $workflow->create(
                    $commission,
                    $user,
                    CommissionAdjustmentDirection::from((string) $data['direction']),
                    (string) $data['amount'],
                    (string) $data['reason'],
                );

                Notification::make()
                    ->title(__('crm.commissions.adjustments.notifications.created'))
                    ->success()
                    ->send();
            });
    }

    public static function approveAction(): Action
    {
        return Action::make('approve_adjustment')
            ->label(__('crm.commissions.adjustments.actions.approve'))
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(fn (OpportunityCommissionAdjustment $record): bool => Auth::user() !== null
                && OpportunityCommissionAdjustmentAccess::canApprove(Auth::user(), $record))
            ->requiresConfirmation()
            ->modalHeading(__('crm.commissions.adjustments.actions.approve'))
            ->schema(fn (OpportunityCommissionAdjustment $record): array => self::approveConfirmationSchema($record))
            ->action(function (OpportunityCommissionAdjustment $record, OpportunityCommissionAdjustmentWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user !== null, 403);

                try {
                    $workflow->approve($record, $user);

                    Notification::make()
                        ->title(__('crm.commissions.adjustments.notifications.approved'))
                        ->success()
                        ->send();
                } catch (HttpException $exception) {
                    if ($exception->getStatusCode() === 409) {
                        Notification::make()
                            ->title(__('crm.commissions.adjustments.errors.already_processed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    throw $exception;
                }
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject_adjustment')
            ->label(__('crm.commissions.adjustments.actions.reject'))
            ->icon(Heroicon::XCircle)
            ->color('danger')
            ->visible(fn (OpportunityCommissionAdjustment $record): bool => Auth::user() !== null
                && OpportunityCommissionAdjustmentAccess::canReject(Auth::user(), $record))
            ->schema([
                Textarea::make('rejection_reason')
                    ->label(__('crm.commissions.adjustments.fields.rejection_reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (OpportunityCommissionAdjustment $record, array $data, OpportunityCommissionAdjustmentWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user !== null, 403);

                try {
                    $workflow->reject($record, $user, (string) $data['rejection_reason']);

                    Notification::make()
                        ->title(__('crm.commissions.adjustments.notifications.rejected'))
                        ->success()
                        ->send();
                } catch (HttpException $exception) {
                    if ($exception->getStatusCode() === 409) {
                        Notification::make()
                            ->title(__('crm.commissions.adjustments.errors.already_processed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    throw $exception;
                }
            });
    }

    public static function cancelAction(): Action
    {
        return Action::make('cancel_adjustment')
            ->label(__('crm.commissions.adjustments.actions.cancel'))
            ->icon(Heroicon::NoSymbol)
            ->color('gray')
            ->visible(fn (OpportunityCommissionAdjustment $record): bool => Auth::user() !== null
                && OpportunityCommissionAdjustmentAccess::canCancel(Auth::user(), $record))
            ->requiresConfirmation()
            ->modalDescription(__('crm.commissions.adjustments.confirmations.cancel'))
            ->action(function (OpportunityCommissionAdjustment $record, OpportunityCommissionAdjustmentWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user !== null, 403);

                try {
                    $workflow->cancel($record, $user);

                    Notification::make()
                        ->title(__('crm.commissions.adjustments.notifications.cancelled'))
                        ->success()
                        ->send();
                } catch (HttpException $exception) {
                    if ($exception->getStatusCode() === 409) {
                        Notification::make()
                            ->title(__('crm.commissions.adjustments.errors.already_processed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    throw $exception;
                }
            });
    }

    /**
     * @return array<int, Action>
     */
    public static function recordActions(): array
    {
        return [
            self::approveAction(),
            self::rejectAction(),
            self::cancelAction(),
        ];
    }

    /**
     * @return array<int, Placeholder|Select|TextInput|Textarea>
     */
    private static function createFormSchema(OpportunityCommission $commission): array
    {
        $commission->loadMissing('adjustments', 'commissionPayments');

        return [
            Select::make('direction')
                ->label(__('crm.commissions.adjustments.fields.direction'))
                ->options(CommissionAdjustmentDirection::options())
                ->required()
                ->live(),
            TextInput::make('amount')
                ->label(__('crm.commissions.adjustments.fields.amount'))
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->step(0.01)
                ->helperText(__('crm.commissions.adjustments.hints.amount_positive'))
                ->live(onBlur: true),
            Textarea::make('reason')
                ->label(__('crm.commissions.adjustments.fields.reason'))
                ->required()
                ->rows(3),
            Placeholder::make('decrease_preview')
                ->label(__('crm.commissions.adjustments.fields.decrease_preview'))
                ->content(function (Get $get) use ($commission): string {
                    if ($get('direction') !== CommissionAdjustmentDirection::DECREASE->value) {
                        return '-';
                    }

                    $amount = (string) ($get('amount') ?? '');

                    if ($amount === '' || ! is_numeric($amount)) {
                        return '-';
                    }

                    $currentEffective = $commission->effectiveCommissionAmount();
                    $projected = CommissionAdjustmentCalculator::projectedBalanceAfter(
                        $currentEffective,
                        CommissionAdjustmentDirection::DECREASE,
                        $amount,
                    );

                    return __('crm.commissions.adjustments.messages.decrease_preview', [
                        'current' => $currentEffective,
                        'amount' => $amount,
                        'projected' => $projected,
                        'net_paid' => $commission->netPaidAmount(),
                    ]);
                })
                ->visible(fn (Get $get): bool => $get('direction') === CommissionAdjustmentDirection::DECREASE->value),
        ];
    }

    /**
     * @return array<int, Placeholder>
     */
    private static function approveConfirmationSchema(OpportunityCommissionAdjustment $record): array
    {
        $record->loadMissing('commission.adjustments', 'commission.commissionPayments');
        $commission = $record->commission;

        abort_unless($commission instanceof OpportunityCommission, 404);

        $currentEffective = $commission->effectiveCommissionAmount();
        $projected = CommissionAdjustmentCalculator::projectedBalanceAfter(
            $currentEffective,
            $record->direction,
            (string) $record->amount,
        );

        return [
            Placeholder::make('current_effective')
                ->label(__('crm.commissions.adjustments.fields.effective_amount'))
                ->content($currentEffective),
            Placeholder::make('direction')
                ->label(__('crm.commissions.adjustments.fields.direction'))
                ->content($record->direction->label()),
            Placeholder::make('amount')
                ->label(__('crm.commissions.adjustments.fields.amount'))
                ->content((string) $record->amount),
            Placeholder::make('resulting_effective')
                ->label(__('crm.commissions.adjustments.fields.resulting_effective_amount'))
                ->content($projected),
        ];
    }

    public static function isRecordActionVisible(OpportunityCommissionAdjustment $record, string $action): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return match ($action) {
            'approve' => OpportunityCommissionAdjustmentAccess::canApprove($user, $record),
            'reject' => OpportunityCommissionAdjustmentAccess::canReject($user, $record),
            'cancel' => OpportunityCommissionAdjustmentAccess::canCancel($user, $record),
            default => false,
        };
    }

    public static function isApprovedRecordMutable(OpportunityCommissionAdjustment $record): bool
    {
        return $record->status !== CommissionAdjustmentStatus::APPROVED;
    }
}
