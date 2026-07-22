<footer class="invoice-footer">
    @if (! empty($data['notes']))
        <div class="footer-block">
            <div class="box-label">{{ $data['labels']['notes'] }}</div>
            <div class="prewrap">{{ $data['notes'] }}</div>
        </div>
    @endif

    @if (! empty($data['terms']))
        <div class="footer-block">
            <div class="box-label">{{ $data['labels']['terms'] }}</div>
            <div class="prewrap">{{ $data['terms'] }}</div>
        </div>
    @endif

    @if (! empty($data['closing_note']))
        <div class="closing-note">{{ $data['closing_note'] }}</div>
    @endif

    <div class="sign-row">
        @if (! empty($data['signature_label']) || ! empty($data['signature_url']) || ! empty($data['authority_name']))
            <div class="sign-box">
                <div class="box-label">{{ $data['signature_label'] }}</div>
                @if (! empty($data['signature_url']))
                    <img src="{{ $data['signature_url'] }}" alt="" class="sign-image">
                @else
                    <div class="sign-line"></div>
                @endif
                @if (! empty($data['authority_name']))
                    <div class="muted">{{ $data['authority_name'] }}</div>
                @endif
            </div>
        @endif

        @if (! empty($data['stamp_label']) || ! empty($data['stamp_url']))
            <div class="sign-box">
                <div class="box-label">{{ $data['stamp_label'] }}</div>
                @if (! empty($data['stamp_url']))
                    <img src="{{ $data['stamp_url'] }}" alt="" class="stamp-image">
                @else
                    <div class="stamp-placeholder"></div>
                @endif
            </div>
        @endif
    </div>

    @if (! empty($data['footer_text']))
        <div class="page-footer muted">{{ $data['footer_text'] }}</div>
    @endif
</footer>
