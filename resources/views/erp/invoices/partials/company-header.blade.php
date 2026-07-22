@php
    $company = $data['company'];
    $align = $data['logo_align'] ?? 'start';
@endphp
<header class="company-header logo-align-{{ $align }}">
    <div class="company-brand">
        @if (! empty($company['logo_url']))
            <img src="{{ $company['logo_url'] }}" alt="" class="company-logo">
        @endif
        <div class="company-text">
            <div class="company-name">{{ $company['name'] }}</div>
            @if (! empty($company['legal_name']) && $company['legal_name'] !== $company['name'])
                <div class="muted">{{ $company['legal_name'] }}</div>
            @endif
            @if (! empty($data['header_text']))
                <div class="header-text">{{ $data['header_text'] }}</div>
            @endif
        </div>
    </div>
    <div class="company-contact">
        @if (! empty($company['address']) || ! empty($company['city']))
            <div>{{ collect([$company['address'] ?? null, $company['city'] ?? null, $company['postal_code'] ?? null, $company['country'] ?? null])->filter()->implode(' · ') }}</div>
        @endif
        @if (! empty($company['phone']))
            <div>{{ $company['phone'] }}</div>
        @endif
        @if (! empty($company['email']))
            <div>{{ $company['email'] }}</div>
        @endif
        @if (! empty($company['website']))
            <div>{{ $company['website'] }}</div>
        @endif
        @if (! empty($company['tax_number']))
            <div>{{ $data['labels']['tax_number'] }}: {{ $company['tax_number'] }}</div>
        @endif
        @if (! empty($company['commercial_register']))
            <div>{{ $data['labels']['commercial_register'] }}: {{ $company['commercial_register'] }}</div>
        @endif
        @if (! empty($company['extra_registration']))
            <div>{{ $company['extra_registration'] }}</div>
        @endif
    </div>
</header>
