<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Actions;

use App\Enums\Crm\CommissionPaymentEntryType;
use App\Models\Tenant\CommissionPayment;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Services\Crm\Commission\CommissionPaymentService;
use App\Support\Crm\Commission\CommissionPaymentCycleAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class CommissionPaymentActions
{
    public static function reverseAction(CommissionPaymentCycle $cycle): Action
    {
        return Action::make('reverse_payment')
            ->label(__('crm.payment_cycles.actions.reverse_payment'))
            ->icon(Heroicon::ArrowUturnLeft)
            ->color('danger')
            ->visible(fn (CommissionPayment $payment): bool => Auth::user() !== null
                && CommissionPaymentCycleAccess::canReversePayment(Auth::user(), $cycle)
                && $payment->entry_type === CommissionPaymentEntryType::PAYMENT
                && ! $payment->reversals()->exists())
            ->schema([
                Textarea::make('reason')
                    ->label(__('crm.payment_cycles.fields.reversal_reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (CommissionPayment $payment, array $data, CommissionPaymentService $paymentService): void {
                $user = Auth::user();
                abort_unless($user !== null, 403);

                try {
                    $paymentService->reversePayment($payment, $user, (string) $data['reason']);

                    Notification::make()
                        ->title(__('crm.payment_cycles.notifications.payment_reversed'))
                        ->success()
                        ->send();
                } catch (HttpException $exception) {
                    if ($exception->getStatusCode() === 409) {
                        Notification::make()
                            ->title(__('crm.commissions.errors.payment_already_reversed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    throw $exception;
                }
            });
    }
}
