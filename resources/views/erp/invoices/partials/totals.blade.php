@php $totals = $data['totals']; @endphp
<section class="totals-section">
    <table class="totals-table">
        @if ($totals['subtotal'] !== null)
            <tr><th>{{ $data['labels']['subtotal'] }}</th><td>{{ $totals['subtotal'] }}</td></tr>
        @endif
        @if ($totals['discount_total'] !== null)
            <tr><th>{{ $data['labels']['discount_total'] }}</th><td>{{ $totals['discount_total'] }}</td></tr>
        @endif
        @if ($totals['tax_total'] !== null)
            <tr><th>{{ $data['labels']['tax_total'] }}</th><td>{{ $totals['tax_total'] }}</td></tr>
        @endif
        @if ($totals['grand_total'] !== null)
            <tr class="grand"><th>{{ $data['labels']['grand_total'] }}</th><td>{{ $totals['grand_total'] }}</td></tr>
        @endif
        @if ($totals['paid_amount'] !== null)
            <tr><th>{{ $data['labels']['paid_amount'] }}</th><td>{{ $totals['paid_amount'] }}</td></tr>
        @endif
        @if ($totals['due_amount'] !== null)
            <tr><th>{{ $data['labels']['due_amount'] }}</th><td>{{ $totals['due_amount'] }}</td></tr>
        @endif
        @if ($totals['payment_status'] !== null)
            <tr><th>{{ $data['labels']['payment_status'] }}</th><td>{{ $totals['payment_status'] }}</td></tr>
        @endif
    </table>
</section>
