<?php

/**
 * Shared Filament header actions for ERP invoice payments.
 */

namespace App\Filament\Tenant\Support\Erp;

use App\Actions\Erp\RecordInvoicePaymentAction;
use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\PaymentMethod;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class ErpPaymentActions
{
    public static function recordPayment(InvoicePayableType $payableType): Action
    {
        return Action::make('recordPayment')
            ->label(__('erp.actions.record_payment'))
            ->color('success')
            ->form([
                TextInput::make('amount')
                    ->label(__('erp.fields.amount'))
                    ->numeric()
                    ->required()
                    ->minValue(0.01),
                Select::make('payment_method')
                    ->label(__('erp.fields.payment_method'))
                    ->options(ErpEnumOptions::options(PaymentMethod::class))
                    ->default(PaymentMethod::Cash->value)
                    ->required()
                    ->native(false),
                TextInput::make('payment_reference')
                    ->label(__('erp.fields.payment_reference'))
                    ->maxLength(255),
                DateTimePicker::make('paid_at')
                    ->label(__('erp.fields.paid_at'))
                    ->default(now()),
                Textarea::make('notes')
                    ->label(__('erp.fields.notes'))
                    ->rows(2),
            ])
            ->visible(fn (Model $record) => in_array($record->status?->value ?? $record->status, [
                'issued', 'partially_paid', 'overdue',
            ], true))
            ->action(function (array $data, Model $record) use ($payableType) {
                try {
                    app(RecordInvoicePaymentAction::class)->execute(
                        $payableType,
                        (int) $record->getKey(),
                        (string) $data['amount'],
                        PaymentMethod::from($data['payment_method']),
                        $data['payment_reference'] ?? null,
                        isset($data['paid_at']) ? (string) $data['paid_at'] : null,
                        $data['notes'] ?? null,
                    );
                    Notification::make()->title(__('erp.notifications.payment_recorded'))->success()->send();
                } catch (ValidationException $e) {
                    Notification::make()
                        ->title(collect($e->errors())->flatten()->first() ?? __('erp.notifications.error'))
                        ->danger()
                        ->send();
                }
            });
    }
}
