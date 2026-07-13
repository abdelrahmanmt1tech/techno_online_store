@extends('legal.layout')

@section('title', 'Terms of Service')

@section('content')
    <h1>Terms of Service</h1>
    <p class="meta">
        Techno Web Masr · Platform:
        <a href="https://online-store.technomasrsystems.com">online-store.technomasrsystems.com</a><br>
        Last updated: {{ date('F j, Y') }} · Contact:
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
    </p>

    <p>
        These Terms of Service (“Terms”) govern use of the multi-tenant CRM and ecommerce messaging
        platform operated by <strong>Techno Web Masr</strong> at
        <code>online-store.technomasrsystems.com</code> (the “Platform”).
        By accessing or using the Platform, you agree to these Terms.
    </p>

    <h2>1. The service</h2>
    <p>
        Techno Web Masr provides a multi-tenant CRM / ecommerce messaging platform that helps merchants
        connect messaging channels (including Facebook Messenger and WhatsApp Business Cloud API),
        receive customer messages, and reply from a store workspace.
    </p>

    <h2>2. Merchant responsibilities</h2>
    <p>Merchants are solely responsible for:</p>
    <ul>
        <li>Their Facebook Pages, WhatsApp Business assets, Meta Business Manager settings, and granted permissions.</li>
        <li>Obtaining and maintaining any required customer consent for messaging.</li>
        <li>The content of messages they send and how they use customer data.</li>
        <li>Complying with Meta Platform Terms, Messenger Platform policies, WhatsApp Business Messaging policies, and all applicable laws.</li>
    </ul>

    <h2>3. Acceptable use</h2>
    <p>You must not use the Platform to:</p>
    <ul>
        <li>Send spam, unsolicited bulk messages, or unauthorized marketing where prohibited.</li>
        <li>Transmit illegal, harmful, deceptive, or abusive content.</li>
        <li>Impersonate others or misrepresent affiliation with Techno Web Masr, Meta, or any third party.</li>
        <li>Attempt unauthorized access to accounts, systems, tokens, or customer data.</li>
        <li>Violate Meta policies or abuse Messenger / WhatsApp messaging features.</li>
    </ul>

    <h2>4. Third-party services (Meta)</h2>
    <p>
        Facebook, Messenger, and WhatsApp are third-party services operated by Meta. Their availability,
        permissions, pricing, review requirements, and policies may change at any time. Techno Web Masr does
        not control Meta products and is not responsible for Meta outages, policy enforcement, App Review
        outcomes, or messaging delivery failures caused by Meta.
    </p>

    <h2>5. Suspension</h2>
    <p>
        We may suspend or disable messaging integrations, accounts, or access that violate these Terms,
        Meta policies, applicable law, or that present a security or abuse risk to the Platform or other users.
    </p>

    <h2>6. No warranty / availability</h2>
    <p>
        The Platform is provided on an “as is” and “as available” basis. We do not guarantee uninterrupted,
        error-free, or continuous service, including delivery of messages through Meta APIs.
    </p>

    <h2>7. Limitation of liability</h2>
    <p>
        To the maximum extent permitted by law, Techno Web Masr is not liable for indirect, incidental,
        special, consequential, or punitive damages, or for lost profits, lost data, or business interruption
        arising from use of the Platform or Meta services. Our aggregate liability relating to the Platform
        is limited to the fees (if any) you paid to Techno Web Masr for the Platform in the three (3) months
        preceding the claim.
    </p>

    <h2>8. Billing with Meta</h2>
    <p>
        Messaging, conversation, or other Meta product charges are typically billed by Meta to the merchant
        (or Meta Business) directly where applicable. Techno Web Masr is not responsible for Meta billing
        unless a separate written agreement states otherwise.
    </p>

    <h2>9. Privacy</h2>
    <p>
        Our collection and use of information is described in the
        <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.
        Requests related to data deletion are described on the
        <a href="{{ route('legal.data-deletion') }}">Data Deletion</a> page.
    </p>

    <h2>10. Contact / support</h2>
    <p>
        Support: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a><br>
        Platform: <a href="https://online-store.technomasrsystems.com">online-store.technomasrsystems.com</a><br>
        Company: Techno Web Masr
    </p>
@endsection
