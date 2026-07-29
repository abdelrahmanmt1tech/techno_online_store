<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.lite.low_stock_list') }}
        </x-slot>

        @if ($items->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.lite.empty_low_stock') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-start text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_item') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_sku') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_qty') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_min') }}</th>
                            <th class="py-2 pe-3 font-medium">{{ __('dashboard.lite.col_warehouse') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pe-3">{{ $item->item_name }}</td>
                                <td class="py-2 pe-3">{{ $item->sku ?: ($item->barcode ?: '—') }}</td>
                                <td class="py-2 pe-3">{{ number_format((float) $item->quantity_on_hand, 2) }}</td>
                                <td class="py-2 pe-3">{{ number_format((float) $item->minimum_stock, 2) }}</td>
                                <td class="py-2 pe-3">{{ $item->warehouse_name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
