@extends('legal.layout')

@section('title', 'Data Deletion')

@section('content')
    <h1>Data Deletion Request</h1>
    <p class="meta">
        Techno Web Masr · Platform:
        <a href="https://online-store.technomasrsystems.com">online-store.technomasrsystems.com</a><br>
        Last updated: {{ date('F j, Y') }} · Contact:
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
    </p>

    <p>
        If you are a merchant or user of the Techno Web Masr platform and want us to delete your account
        or related messaging data, you can submit a manual deletion request by email.
        An automated deletion API endpoint is not required for Meta app review at this time.
    </p>

    <h2>How to request deletion</h2>
    <ol>
        <li>
            Send an email to
            <a href="mailto:{{ $supportEmail }}?subject=Data%20Deletion%20Request">{{ $supportEmail }}</a>
            with the subject line <strong>Data Deletion Request</strong>.
        </li>
        <li>Include the identifiers listed below so we can locate the correct account.</li>
        <li>We will confirm receipt and process the request manually.</li>
    </ol>

    <h2>Identifiers to include</h2>
    <ul>
        <li>Your account email address used on the Platform</li>
        <li>Your store / tenant name (and domain if known)</li>
        <li>Facebook Page ID(s), if Messenger was connected</li>
        <li>WhatsApp phone number ID or display number, if WhatsApp was connected (optional but helpful)</li>
        <li>Any other details that help verify ownership of the account</li>
    </ul>

    <h2>What may be deleted</h2>
    <p>Subject to legal and security retention requirements, deletion may include:</p>
    <ul>
        <li>Connected Facebook Pages and related Messenger configuration</li>
        <li>Stored access tokens (encrypted credentials used for messaging APIs)</li>
        <li>Messenger / WhatsApp conversations, messages, and related CRM contact records for that merchant</li>
        <li>Central routing registry entries associated with those connections</li>
        <li>Onboarding session records related to Facebook Login / Embedded Signup flows</li>
    </ul>

    <h2>Retention exceptions</h2>
    <p>
        We may retain limited records where required for legal compliance, fraud prevention, security
        investigations, dispute resolution, or audit obligations. Where feasible, retained records will be
        minimized and protected.
    </p>

    <h2>Contact</h2>
    <p>
        Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a><br>
        Platform: <a href="https://online-store.technomasrsystems.com">online-store.technomasrsystems.com</a><br>
        Company: Techno Web Masr<br>
        Related:
        <a href="{{ route('legal.privacy') }}">Privacy Policy</a> ·
        <a href="{{ route('legal.terms') }}">Terms of Service</a>
    </p>
@endsection
