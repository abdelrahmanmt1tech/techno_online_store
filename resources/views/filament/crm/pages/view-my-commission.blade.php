<x-filament-panels::page>
    <div class="mb-4">
        <x-filament::button
            tag="a"
            :href="\App\Filament\Crm\Pages\MyCommissions::getUrl()"
            color="gray"
            icon="heroicon-m-arrow-left"
        >
            {{ __('dashboard.crm.own_commissions.actions.back_to_list') }}
        </x-filament::button>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.crm.own_commissions.sections.details') }}
        </x-slot>

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.fields.opportunity') }}</dt>
                <dd class="font-medium">{{ $record->opportunity?->title ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.fields.client') }}</dt>
                <dd class="font-medium">{{ $record->opportunity?->client?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.commissions.fields.commission_type') }}</dt>
                <dd class="font-medium">{{ $this->typeLabel($record->commission_type) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.commissions.fields.base_amount') }}</dt>
                <dd class="font-medium">{{ $this->formatMoney((string) $record->base_amount) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.commissions.fields.commission_percentage') }}</dt>
                <dd class="font-medium">{{ $this->formatMoney((string) $record->commission_percentage) }}%</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.fields.original_amount') }}</dt>
                <dd class="font-medium">{{ $this->formatMoney((string) $record->commission_amount) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.fields.increase_adjustments') }}</dt>
                <dd class="font-medium">{{ $this->formatMoney($record->approvedIncreaseAdjustmentsTotal()) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.fields.decrease_adjustments') }}</dt>
                <dd class="font-medium">{{ $this->formatMoney($record->approvedDecreaseAdjustmentsTotal()) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.fields.effective_amount') }}</dt>
                <dd class="font-medium">{{ $this->formatMoney($record->effectiveCommissionAmount()) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.fields.net_paid') }}</dt>
                <dd class="font-medium">{{ $this->formatMoney($record->netPaidAmount()) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.fields.remaining') }}</dt>
                <dd class="font-medium">{{ $this->formatMoney($record->remaining_amount) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.fields.status') }}</dt>
                <dd class="font-medium">{{ $this->statusLabel($record->status) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.fields.approved_at') }}</dt>
                <dd class="font-medium">{{ $record->approved_at?->format('Y-m-d H:i') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.fields.due_at') }}</dt>
                <dd class="font-medium">{{ $record->due_at?->format('Y-m-d') ?? '-' }}</dd>
            </div>
            @if ($record->notes)
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.fields.notes') }}</dt>
                    <dd class="font-medium">{{ $record->notes }}</dd>
                </div>
            @endif
        </dl>
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            {{ __('dashboard.crm.own_commissions.sections.adjustments') }}
        </x-slot>

        @if (count($this->adjustments) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.empty.adjustments') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-start">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-3 py-2">{{ __('dashboard.crm.commissions.adjustments.fields.direction') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.crm.commissions.adjustments.fields.amount') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.crm.fields.status') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.crm.commissions.adjustments.fields.reason') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.crm.own_commissions.fields.approved_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->adjustments as $adjustment)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2">{{ $this->adjustmentDirectionLabel($adjustment->direction) }}</td>
                                <td class="px-3 py-2">{{ $this->formatMoney((string) $adjustment->amount) }}</td>
                                <td class="px-3 py-2">{{ $this->adjustmentStatusLabel($adjustment->status) }}</td>
                                <td class="px-3 py-2">{{ $adjustment->reason }}</td>
                                <td class="px-3 py-2">{{ $adjustment->approved_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    @if ($this->canViewPayments())
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                {{ __('dashboard.crm.own_commissions.sections.payments') }}
            </x-slot>

            @if (count($this->payments) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.empty.payments') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-start">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-3 py-2">{{ __('dashboard.crm.own_commissions.fields.cycle_number') }}</th>
                                <th class="px-3 py-2">{{ __('dashboard.crm.payment_cycles.fields.entry_type') }}</th>
                                <th class="px-3 py-2">{{ __('dashboard.crm.payment_cycles.fields.amount') }}</th>
                                <th class="px-3 py-2">{{ __('dashboard.crm.own_commissions.fields.remaining_before') }}</th>
                                <th class="px-3 py-2">{{ __('dashboard.crm.own_commissions.fields.remaining_after') }}</th>
                                <th class="px-3 py-2">{{ __('dashboard.crm.payment_cycles.fields.payment_method') }}</th>
                                <th class="px-3 py-2">{{ __('dashboard.crm.payment_cycles.fields.reference_number') }}</th>
                                <th class="px-3 py-2">{{ __('dashboard.crm.payment_cycles.fields.executed_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->payments as $payment)
                                <tr @class([
                                    'border-b border-gray-100 dark:border-gray-800',
                                    'bg-danger-50/50 dark:bg-danger-950/20' => $payment->entry_type === \App\Enums\CommissionPaymentEntryType::REVERSAL,
                                ])>
                                    <td class="px-3 py-2">{{ $payment->commissionPaymentCycle?->cycle_number ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $this->paymentEntryLabel($payment->entry_type) }}</td>
                                    <td class="px-3 py-2">{{ $this->formatMoney((string) $payment->amount) }}</td>
                                    <td class="px-3 py-2">{{ $this->formatMoney((string) $payment->paid_amount_before) }}</td>
                                    <td class="px-3 py-2">{{ $this->formatMoney((string) $payment->remaining_amount_after) }}</td>
                                    <td class="px-3 py-2">{{ $this->paymentMethodLabel($payment->payment_method) }}</td>
                                    <td class="px-3 py-2">{{ $payment->reference_number ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $payment->executed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            {{ __('dashboard.crm.own_commissions.sections.audit') }}
        </x-slot>

        @if ($record->auditLogs->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.crm.own_commissions.empty.audit') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-start">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-3 py-2">{{ __('dashboard.crm.commissions.fields.audit_action') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.crm.fields.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($record->auditLogs->sortByDesc('created_at') as $log)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2">{{ __('dashboard.crm.commissions.audit_actions.'.$log->action) }}</td>
                                <td class="px-3 py-2">{{ $log->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
