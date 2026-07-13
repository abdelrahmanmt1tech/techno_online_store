@extends('legal.layout')

@section('title', 'Privacy Policy')

@section('content')
    <h1>Privacy Policy</h1>
    <p class="meta">
        Techno Web Masr · Platform:
        <a href="https://online-store.technomasrsystems.com">online-store.technomasrsystems.com</a><br>
        Last updated: {{ date('F j, Y') }} · Contact:
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
    </p>

    <p>
        This Privacy Policy explains how <strong>Techno Web Masr</strong> (“we”, “us”, or “our”) collects,
        uses, stores, and protects information when merchants use our multi-tenant CRM and ecommerce
        messaging platform at <code>online-store.technomasrsystems.com</code> (the “Platform”), including
        Facebook Messenger and WhatsApp Business Cloud API integrations.
    </p>

    <h2>1. Information we collect</h2>
    <p>Depending on how a merchant uses the Platform, we may process:</p>
    <ul>
        <li><strong>Merchant account data:</strong> name, email address, login credentials, and session details.</li>
        <li><strong>Facebook / Messenger connection data:</strong> connected Facebook Page data such as Page ID, Page name, Page access tokens, Messenger conversations, messages, customer sender PSID, and profile name / profile picture URL when returned by Meta.</li>
        <li><strong>WhatsApp connection data (if connected):</strong> WhatsApp Business Account (WABA) ID, phone number ID, display phone number, webhook events, messages, and related contact records created from customer interactions.</li>
        <li><strong>Operational / diagnostic data:</strong> webhook event metadata, API request diagnostics, and security logs needed to operate and troubleshoot the service.</li>
    </ul>

    <h2>2. How we use information</h2>
    <p>We use this information to:</p>
    <ul>
        <li>Provide CRM messaging features for merchants and their customers.</li>
        <li>Route inbound Meta webhooks to the correct merchant (tenant) store.</li>
        <li>Allow merchants to view conversations and reply to customers within applicable messaging windows and policies.</li>
        <li>Operate diagnostics, security monitoring, abuse prevention, and support.</li>
        <li>Maintain and improve Platform reliability and compliance with Meta platform requirements.</li>
    </ul>

    <h2>3. Access tokens</h2>
    <p>
        Messenger Page access tokens and WhatsApp access tokens are stored <strong>encrypted</strong> in the
        merchant’s tenant database. Tokens are never displayed in full in the Platform UI and are not stored
        in our central routing registry.
    </p>

    <h2>4. Central registry</h2>
    <p>
        The Platform maintains a central registry used only for routing and status metadata
        (for example Page ID or phone number ID mapped to a tenant). The registry does
        <strong>not</strong> store access tokens.
    </p>

    <h2>5. Data sharing</h2>
    <p>
        We do <strong>not</strong> sell personal data. We share data with Meta (Facebook, Messenger, WhatsApp)
        only as required to operate the messaging APIs that merchants connect (for example sending and
        receiving messages, verifying webhooks, and managing Page / WhatsApp subscriptions).
        We may also disclose information if required by law or to protect the security and integrity of the Platform.
    </p>

    <h2>6. Retention</h2>
    <p>
        We retain merchant and messaging data while the merchant account remains active, and for as long as
        reasonably necessary for legal, security, dispute-resolution, or operational purposes after that.
    </p>

    <h2>7. Deletion</h2>
    <p>
        Merchants and users may request deletion of their account and related Platform data by contacting
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> or by following the instructions on our
        <a href="{{ route('legal.data-deletion') }}">Data Deletion</a> page.
        Deletion requests are handled manually and may be subject to legal or security retention requirements.
    </p>

    <h2>8. Security</h2>
    <p>
        We apply technical and organizational measures appropriate to the Platform, including encryption of
        access tokens at rest, authenticated access controls for admin and merchant panels, and redaction or
        minimization of sensitive values in logs where applicable. No method of transmission or storage is
        completely secure; merchants should protect their Meta credentials and account access.
    </p>

    <h2>9. Third-party services</h2>
    <p>
        The Platform integrates with Meta products and APIs, including Facebook Login, Facebook Pages,
        Messenger, and WhatsApp Business Cloud API. Meta’s own terms and privacy policies also apply to
        data processed by Meta.
    </p>

    <h2>10. Contact</h2>
    <p>
        Questions about this Privacy Policy:
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a><br>
        Platform: <a href="https://online-store.technomasrsystems.com">online-store.technomasrsystems.com</a><br>
        Company: Techno Web Masr
    </p>
@endsection
