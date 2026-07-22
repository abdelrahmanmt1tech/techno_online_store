<!DOCTYPE html>
<html lang="{{ $data['locale'] }}" dir="{{ $data['direction'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data['title'] }} — {{ $data['document_number'] }}</title>
    <style>{!! file_get_contents(resource_path('css/erp-invoice-print.css')) !!}</style>
    <style>
        :root {
            --invoice-accent: {{ $data['primary_color'] ?: '#065f46' }};
            --logo-width: {{ (int) ($data['logo_width'] ?: 140) }}px;
        }
        @page {
            size: {{ $data['paper_size'] ?? 'A4' }} {{ $data['paper_orientation'] ?? 'portrait' }};
            margin: 12mm;
        }
    </style>
</head>
<body class="invoice-body paper-{{ strtolower($data['paper_size'] ?? 'a4') }} orient-{{ $data['paper_orientation'] ?? 'portrait' }}">
    <div class="no-print toolbar">
        <button type="button" onclick="window.print()">{{ $data['labels']['print'] }}</button>
        <a href="{{ $backUrl }}">{{ $data['labels']['back'] }}</a>
    </div>

    <div class="invoice-sheet">
        @if (! empty($data['watermark']))
            <div class="watermark" aria-hidden="true">{{ __('erp.print.watermark.'.$data['watermark'], [], $data['locale']) }}</div>
        @endif

        @include('erp.invoices.partials.company-header')
        @include('erp.invoices.partials.document-meta')
        @include('erp.invoices.partials.items-table')
        @include('erp.invoices.partials.totals')
        @include('erp.invoices.partials.footer')
    </div>

    @if (! empty($autoprint))
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endif
</body>
</html>
