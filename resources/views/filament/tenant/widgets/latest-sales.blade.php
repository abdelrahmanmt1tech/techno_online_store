<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.lite.latest_sales') }}
        </x-slot>

        @if ($sales->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.lite.empty_sales') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-start text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_reference') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_datetime') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_customer') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_total') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_status') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_source') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pe-3">
                                    @if ($canOpen)
                                        <a href="{{ \App\Filament\Tenant\Resources\Sales\SaleResource::getUrl('view', ['record' => $sale]) }}" class="text-primary-600 hover:underline">
                                            {{ $sale->document_number ?: $sale->receipt_number ?: '#'.$sale->id }}
                                        </a>
                                    @else
                                        {{ $sale->document_number ?: $sale->receipt_number ?: '#'.$sale->id }}
                                    @endif
                                </td>
                                <td class="py-2 pe-3 whitespace-nowrap">
                                    {{ optional($sale->sale_date)?->format('Y-m-d') }}
                                    @if ($sale->created_at)
                                        <span class="text-gray-400">{{ $sale->created_at->format('H:i') }}</span>
                                    @endif
                                </td>
                                <td class="py-2 pe-3">{{ $sale->customer?->name ?: '—' }}</td>
                                <td class="py-2 pe-3 whitespace-nowrap">{{ number_format((float) $sale->grand_total, 2) }} {{ $sale->currency_code }}</td>
                                <td class="py-2 pe-3">{{ $sale->status?->label() }}</td>
                                <td class="py-2 pe-3">{{ $sale->source_type?->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
