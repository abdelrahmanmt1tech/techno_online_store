<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Actions;

use App\Enums\Crm\CommissionStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Services\Crm\Commission\OpportunityCommissionWorkflowService;
use App\Support\Crm\Commission\OpportunityCommissionAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

final class OpportunityCommissionActions
{
    /**
     * @return array<int, Action>
     */
    public static function headerActions(?OpportunityCommission $record = null): array
    {
        return [
            self::submitAction($record),
            self::approveAction($record),
            self::rejectAction($record),
            self::cancelAction($record),
            self::recalculateAction($record),
        ];
    }

    private static function submitAction(?OpportunityCommission $record): Action
    {
        return Action::make('submit_for_approval')
            ->label(__('crm.commissions.actions.submit'))
            ->icon(Heroicon::PaperAirplane)
            ->color('warning')
            ->visible(fn (): bool => self::visibleFor($record, fn (User $user, OpportunityCommission $commission): bool => $commission->status === CommissionStatus::DRAFT
                && $user->can('crm_commissions.update')
                && OpportunityCommissionAccess::canUpdate($user, $commission)))
            ->action(function (OpportunityCommission $commission, OpportunityCommissionWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof User, 403);

                $workflow->submitForApproval($commission, $user);

                Notification::make()
                    ->title(__('crm.commissions.notifications.submitted'))
                    ->success()
                    ->send();
            });
    }

    private static function approveAction(?OpportunityCommission $record): Action
    {
        return Action::make('approve')
            ->label(__('crm.commissions.actions.approve'))
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (): bool => self::visibleFor($record, fn (User $user, OpportunityCommission $commission): bool => OpportunityCommissionAccess::canApprove($user, $commission)))
            ->action(function (OpportunityCommission $commission, OpportunityCommissionWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof User, 403);

                $workflow->approve($commission, $user);

                Notification::make()
                    ->title(__('crm.commissions.notifications.approved'))
                    ->success()
                    ->send();
            });
    }

    private static function rejectAction(?OpportunityCommission $record): Action
    {
        return Action::make('reject')
            ->label(__('crm.commissions.actions.reject'))
            ->icon(Heroicon::XCircle)
            ->color('danger')
            ->visible(fn (): bool => self::visibleFor($record, fn (User $user, OpportunityCommission $commission): bool => OpportunityCommissionAccess::canReject($user, $commission)))
            ->schema([
                Textarea::make('reason')
                    ->label(__('crm.commissions.fields.rejection_reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (OpportunityCommission $commission, array $data, OpportunityCommissionWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof User, 403);

                $workflow->reject($commission, $user, (string) $data['reason']);

                Notification::make()
                    ->title(__('crm.commissions.notifications.rejected'))
                    ->success()
                    ->send();
            });
    }

    private static function cancelAction(?OpportunityCommission $record): Action
    {
        return Action::make('cancel')
            ->label(__('crm.commissions.actions.cancel'))
            ->icon(Heroicon::NoSymbol)
            ->color('gray')
            ->visible(fn (): bool => self::visibleFor($record, fn (User $user, OpportunityCommission $commission): bool => OpportunityCommissionAccess::canCancel($user, $commission)))
            ->schema([
                Textarea::make('reason')
                    ->label(__('crm.commissions.fields.cancellation_reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (OpportunityCommission $commission, array $data, OpportunityCommissionWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof User, 403);

                $workflow->cancel($commission, $user, (string) $data['reason']);

                Notification::make()
                    ->title(__('crm.commissions.notifications.cancelled'))
                    ->success()
                    ->send();
            });
    }

    private static function recalculateAction(?OpportunityCommission $record): Action
    {
        return Action::make('recalculate')
            ->label(__('crm.commissions.actions.recalculate'))
            ->icon(Heroicon::Calculator)
            ->color('info')
            ->visible(fn (): bool => self::visibleFor($record, fn (User $user, OpportunityCommission $commission): bool => OpportunityCommissionAccess::canRecalculate($user, $commission)))
            ->schema(function (OpportunityCommissionWorkflowService $workflow) use ($record): array {
                if (! $record instanceof OpportunityCommission) {
                    return [];
                }

                $preview = $workflow->previewRecalculate($record);

                return [
                    Placeholder::make('preview')
                        ->label(__('crm.commissions.fields.recalculate_preview'))
                        ->content(fn (): string => __('crm.commissions.messages.recalculate_preview', [
                            'old_base' => $record->base_amount,
                            'new_base' => $preview['base_amount'],
                            'old_amount' => $record->commission_amount,
                            'new_amount' => $preview['commission_amount'],
                            'percentage' => $record->commission_percentage,
                        ])),
                ];
            })
            ->requiresConfirmation()
            ->modalDescription(__('crm.commissions.confirmations.recalculate'))
            ->action(function (OpportunityCommission $commission, OpportunityCommissionWorkflowService $workflow): void {
                $user = Auth::user();
                abort_unless($user instanceof User, 403);

                $workflow->recalculate($commission, $user);

                Notification::make()
                    ->title(__('crm.commissions.notifications.recalculated'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Filament 5 header actions do not inject the page record into visible()/schema() closures.
     * Bind the record explicitly when building actions on View/Edit pages.
     *
     * @param  callable(User, OpportunityCommission): bool  $check
     */
    private static function visibleFor(?OpportunityCommission $record, callable $check): bool
    {
        $user = Auth::user();

        if (! $record instanceof OpportunityCommission || ! $user instanceof User) {
            return false;
        }

        return $check($user, $record);
    }
}
