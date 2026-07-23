<section class="doc-meta">
    <div class="doc-title-block">
        <h1 class="doc-title">{{ $data['title'] }}</h1>
        <div class="doc-number">{{ $data['labels']['invoice_number'] }}: <strong>{{ $data['document_number'] }}</strong></div>
        @if ($data['display']['show_status'] ?? true)
            <div>{{ $data['labels']['status'] }}: {{ $data['status'] }}</div>
        @endif
    </div>

    <div class="doc-grid">
        <div class="party-box">
            <div class="box-label">{{ $data['party']['label'] }}</div>
            @if (! empty($data['party']['name']))
                <div class="party-name">{{ $data['party']['name'] }}</div>
            @endif
            @foreach (['phone', 'email', 'address', 'tax_number'] as $field)
                @if (! empty($data['party'][$field]))
                    <div>{{ $data['party'][$field] }}</div>
                @endif
            @endforeach
        </div>

        <div class="meta-box">
            @if (($data['display']['show_invoice_date'] ?? true) && ! empty($data['invoice_date']))
                <div><span>{{ $data['labels']['invoice_date'] }}</span><strong>{{ $data['invoice_date'] }}</strong></div>
            @endif
            @if (($data['display']['show_due_date'] ?? true) && ! empty($data['due_date']))
                <div><span>{{ $data['labels']['due_date'] }}</span><strong>{{ $data['due_date'] }}</strong></div>
            @endif
            @foreach ($data['meta'] as $key => $value)
                @php
                    $labelKey = match ($key) {
                        'sale_number' => 'sale_number',
                        'order_number' => 'order_number',
                        'purchase_order' => 'purchase_order',
                        'goods_receipt' => 'goods_receipt',
                        'branch_name' => 'branch',
                        'supplier_invoice_number' => 'supplier_invoice_number',
                        default => null,
                    };
                @endphp
                @if ($labelKey && ! in_array($key, ['branch_phone', 'branch_address', 'created_by'], true))
                    <div><span>{{ $data['labels'][$labelKey] }}</span><strong>{{ $value }}</strong></div>
                @endif
            @endforeach
            @if (! empty($data['meta']['branch_phone']) || ! empty($data['meta']['branch_address']))
                <div class="muted">
                    {{ collect([$data['meta']['branch_phone'] ?? null, $data['meta']['branch_address'] ?? null])->filter()->implode(' · ') }}
                </div>
            @endif
            @if (! empty($data['currency_code']))
                <div><span>{{ __('erp.fields.currency_code', [], $data['locale']) }}</span><strong>{{ $data['currency_code'] }}</strong></div>
            @endif
        </div>
    </div>
</section>
