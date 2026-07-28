<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->receipt_number ?: $sale->document_number }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; margin: 0; padding: 16px; color: #111; }
        .receipt { max-width: 360px; margin: 0 auto; }
        h1 { font-size: 18px; margin: 0 0 8px; text-align: center; }
        .muted { color: #555; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: start; padding: 4px 0; font-size: 13px; }
        .right { text-align: end; }
        .totals td { padding-top: 6px; }
        .grand { font-weight: bold; font-size: 15px; }
        hr { border: none; border-top: 1px dashed #999; margin: 10px 0; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <button class="no-print" onclick="window.print()">{{ __('erp.actions.print') }}</button>
    <h1>{{ $sale->branch?->name ?: config('app.name') }}</h1>
    <div class="muted" style="text-align:center">
        {{ $sale->posRegister?->name }} · {{ $sale->cashierSession?->user?->name }}
    </div>
    <hr>
    <div class="muted">{{ __('commerce.fields.receipt_number') ?? 'Receipt' }}: <strong>{{ $sale->receipt_number ?: $sale->document_number }}</strong></div>
    <div class="muted">{{ $sale->created_at }}</div>
    <div class="muted">{{ __('commerce.fields.customer') ?? 'Customer' }}: {{ $sale->customer?->name ?: 'Walk-in' }}</div>
    <table>
        <thead>
        <tr>
            <th>{{ __('erp.fields.description') ?? 'Item' }}</th>
            <th class="right">Qty</th>
            <th class="right">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->description_snapshot }}<div class="muted">{{ $item->sku_snapshot }}</div></td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ $item->line_total }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <hr>
    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ $sale->subtotal }}</td></tr>
        <tr><td>Discount</td><td class="right">{{ $sale->discount_total }}</td></tr>
        <tr><td>Tax</td><td class="right">{{ $sale->tax_total }}</td></tr>
        <tr class="grand"><td>Total</td><td class="right">{{ $sale->grand_total }}</td></tr>
        @if($invoice)
            <tr><td>Paid</td><td class="right">{{ $paid ?? $invoice->paid_amount }}</td></tr>
            <tr><td>Change</td><td class="right">{{ $change ?? '0.00' }}</td></tr>
            <tr><td>Due</td><td class="right">{{ $invoice->due_amount }}</td></tr>
        @endif
    </table>
    @if($sale->notes)
        <hr>
        <div class="muted">{{ $sale->notes }}</div>
    @endif
    <script>
        window.addEventListener('load', function () {
            if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
                setTimeout(function () { window.print(); }, 200);
            }
        });
    </script>
</div>
</body>
</html>
