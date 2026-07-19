<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Techno Online Store | Ecommerce, CRM and Business Messaging Platform</title>
    <meta name="description" content="Techno Online Store by Techno Web Masr is a multi-tenant ecommerce, CRM and business messaging platform with WhatsApp and Messenger integration.">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Techno Online Store | Ecommerce, CRM and Business Messaging Platform">
    <meta property="og:description" content="Techno Online Store by Techno Web Masr is a multi-tenant ecommerce, CRM and business messaging platform with WhatsApp and Messenger integration.">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="Techno Online Store">
    <style>
        :root {
            --bg: #f4f7f6;
            --surface: #ffffff;
            --text: #10231f;
            --muted: #5b6b67;
            --border: #d7e2de;
            --accent: #0f766e;
            --accent-dark: #0b5a54;
            --accent-soft: #e6f4f2;
            --ink: #0b1f1c;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "Segoe UI", ui-sans-serif, system-ui, -apple-system, sans-serif;
            color: var(--text);
            background:
                radial-gradient(ellipse 80% 50% at 100% -10%, #d9efe9 0%, transparent 55%),
                radial-gradient(ellipse 60% 40% at 0% 0%, #e8f0ee 0%, transparent 50%),
                var(--bg);
            line-height: 1.6;
        }
        h1, h2, h3, .sans, .nav, .btn, .eyebrow, .footer-nav, .meta-note {
            font-family: "Segoe UI", ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        a { color: var(--accent-dark); }
        .wrap {
            width: min(1100px, calc(100% - 2rem));
            margin-inline: auto;
        }
        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(10px);
            background: color-mix(in srgb, var(--surface) 88%, transparent);
            border-bottom: 1px solid var(--border);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.9rem 0;
            flex-wrap: wrap;
        }
        .brand {
            text-decoration: none;
            color: var(--ink);
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .brand small {
            display: block;
            font-weight: 500;
            color: var(--muted);
            font-size: 0.78rem;
            margin-top: 0.1rem;
        }
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem 1.1rem;
            font-size: 0.92rem;
        }
        .nav a {
            text-decoration: none;
            color: var(--muted);
        }
        .nav a:hover { color: var(--accent); }
        .hero {
            padding: 4.5rem 0 3.5rem;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 2.5rem;
            align-items: center;
        }
        .eyebrow {
            display: inline-block;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 0.85rem;
        }
        h1 {
            margin: 0 0 1rem;
            font-size: clamp(2rem, 4vw, 3.1rem);
            line-height: 1.12;
            letter-spacing: -0.03em;
            color: var(--ink);
            max-width: 16ch;
        }
        .lede {
            margin: 0 0 1.6rem;
            font-size: 1.12rem;
            color: var(--muted);
            max-width: 42rem;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.15rem;
            border-radius: 0.55rem;
            text-decoration: none;
            font-weight: 650;
            font-size: 0.98rem;
            border: 1px solid transparent;
            transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: var(--accent);
            color: #fff;
        }
        .btn-primary:hover { background: var(--accent-dark); }
        .btn-secondary {
            background: transparent;
            color: var(--ink);
            border-color: var(--border);
        }
        .hero-panel {
            background: linear-gradient(160deg, #0f766e 0%, #134e4a 100%);
            color: #ecfdf8;
            border-radius: 1.25rem;
            padding: 1.6rem;
            min-height: 16rem;
            box-shadow: 0 18px 40px rgba(15, 118, 110, 0.22);
            position: relative;
            overflow: hidden;
        }
        .hero-panel::after {
            content: "";
            position: absolute;
            inset: auto -20% -30% 20%;
            height: 70%;
            background: radial-gradient(circle, rgba(255,255,255,0.18), transparent 65%);
        }
        .hero-panel h2 {
            margin: 0 0 0.75rem;
            font-size: 1.2rem;
            position: relative;
            z-index: 1;
        }
        .hero-panel ul {
            margin: 0;
            padding-inline-start: 1.1rem;
            position: relative;
            z-index: 1;
            color: #d1fae5;
        }
        .hero-panel li + li { margin-top: 0.45rem; }
        section {
            padding: 2.75rem 0;
        }
        .section-title {
            margin: 0 0 0.55rem;
            font-size: clamp(1.45rem, 2.5vw, 1.9rem);
            letter-spacing: -0.02em;
            color: var(--ink);
        }
        .section-intro {
            margin: 0 0 1.5rem;
            color: var(--muted);
            max-width: 46rem;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.9rem;
            padding: 1.15rem 1.1rem;
        }
        .card h3 {
            margin: 0 0 0.45rem;
            font-size: 1.02rem;
            color: var(--ink);
        }
        .card p {
            margin: 0;
            color: var(--muted);
            font-size: 0.96rem;
            font-family: "Segoe UI", ui-sans-serif, system-ui, sans-serif;
        }
        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            counter-reset: step;
        }
        .step {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.9rem;
            padding: 1.15rem;
        }
        .step::before {
            counter-increment: step;
            content: counter(step);
            display: inline-flex;
            width: 1.7rem;
            height: 1.7rem;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-dark);
            font-family: "Segoe UI", ui-sans-serif, system-ui, sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 0.7rem;
        }
        .step h3 {
            margin: 0 0 0.4rem;
            font-size: 1rem;
        }
        .step p {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
            font-family: "Segoe UI", ui-sans-serif, system-ui, sans-serif;
        }
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.4rem 1.3rem;
        }
        .panel ul { margin: 0.75rem 0 0; padding-inline-start: 1.2rem; }
        .panel li + li { margin-top: 0.4rem; }
        .meta-note {
            margin-top: 1rem;
            padding: 0.85rem 1rem;
            background: var(--accent-soft);
            border-radius: 0.65rem;
            color: var(--accent-dark);
            font-size: 0.92rem;
        }
        .company-block {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 1.25rem;
        }
        .cta-band {
            margin: 1rem 0 2rem;
            background: var(--ink);
            color: #ecfdf8;
            border-radius: 1.1rem;
            padding: 1.6rem 1.4rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
        }
        .cta-band h2 { margin: 0 0 0.35rem; color: #fff; font-size: 1.35rem; }
        .cta-band p { margin: 0; color: #99f6e4; max-width: 36rem; }
        .cta-band .btn-primary { background: #14b8a6; }
        .cta-band .btn-secondary {
            color: #ecfdf8;
            border-color: rgba(255,255,255,0.28);
        }
        .site-footer {
            border-top: 1px solid var(--border);
            background: var(--surface);
            padding: 2rem 0 2.4rem;
            margin-top: 1rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr;
            gap: 1.5rem;
        }
        .footer-grid h3 {
            margin: 0 0 0.65rem;
            font-size: 0.95rem;
        }
        .footer-nav {
            display: grid;
            gap: 0.45rem;
            font-size: 0.92rem;
        }
        .footer-nav a {
            text-decoration: none;
            color: var(--muted);
        }
        .footer-nav a:hover { color: var(--accent); }
        .copy {
            margin-top: 1.5rem;
            color: var(--muted);
            font-size: 0.88rem;
            font-family: "Segoe UI", ui-sans-serif, system-ui, sans-serif;
        }
        @media (max-width: 900px) {
            .hero, .company-block, .footer-grid { grid-template-columns: 1fr; }
            .card-grid, .steps { grid-template-columns: 1fr 1fr; }
            h1 { max-width: none; }
        }
        @media (max-width: 640px) {
            .card-grid, .steps { grid-template-columns: 1fr; }
            .hero { padding-top: 2.75rem; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="wrap header-inner sans">
            <a class="brand" href="{{ $canonicalUrl }}">
                Techno Online Store
                <small>by Techno Web Masr</small>
            </a>
            <nav class="nav" aria-label="Primary">
                <a href="#about">About</a>
                <a href="#capabilities">Capabilities</a>
                <a href="#meta-data">Meta Platform Data</a>
                <a href="#company">Company</a>
                <a href="{{ $contactUrl }}">Contact</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="wrap hero">
            <div>
                <span class="eyebrow">SaaS platform · Techno Web Masr</span>
                <h1>Commerce, CRM and Business Messaging in One Platform</h1>
                <p class="lede">
                    Techno Online Store is a multi-tenant SaaS platform developed and operated by Techno Web Masr.
                    It helps businesses manage their online store, customers and business communications from one secure dashboard.
                </p>
                <div class="actions">
                    <a class="btn btn-primary" href="{{ $contactUrl }}">Contact Us</a>
                    <a class="btn btn-secondary" href="{{ $platformUrl }}">Access Platform</a>
                </div>
            </div>
            <aside class="hero-panel" aria-label="Platform summary">
                <h2>Built for business clients</h2>
                <ul>
                    <li>Isolated tenant workspaces</li>
                    <li>WhatsApp and Messenger integrations</li>
                    <li>CRM inbox and connection monitoring</li>
                    <li>Operated by Techno Web Masr</li>
                </ul>
            </aside>
        </section>

        <section class="wrap" id="about">
            <h2 class="section-title">About the platform</h2>
            <p class="section-intro">
                Techno Online Store is provided by <strong>Techno Web Masr</strong> to business clients.
                Each business receives its own isolated account (tenant workspace) and manages only its own store data,
                customers, conversations and connected Meta business assets. Techno Web Masr acts as the software company
                and Tech Provider that develops, hosts and supports the platform.
            </p>
            <div class="panel">
                <p style="margin:0;font-family:'Segoe UI',ui-sans-serif,system-ui,sans-serif;color:var(--muted);">
                    Product:
                    <strong style="color:var(--ink);">Techno Online Store</strong>
                    · Company:
                    <a href="{{ $companyUrl }}">Techno Web Masr</a>
                    · Application:
                    <a href="{{ $platformUrl }}">{{ $platformUrl }}</a>
                    · Company product page:
                    <a href="{{ $companyProductUrl }}">{{ $companyProductUrl }}</a>
                </p>
            </div>
        </section>

        <section class="wrap" id="capabilities">
            <h2 class="section-title">Main platform capabilities</h2>
            <p class="section-intro">
                Manual WhatsApp and Messenger integrations are operational for connected business assets.
                Self-service onboarding flows are implemented. Some Meta onboarding paths may still require final account,
                permission and real-number validation before production use.
            </p>
            <div class="card-grid">
                <article class="card"><h3>Multi-tenant store management</h3><p>Each client operates an isolated online store workspace.</p></article>
                <article class="card"><h3>Customer and CRM management</h3><p>Maintain customer records created from business interactions.</p></article>
                <article class="card"><h3>Customer conversations and inbox</h3><p>Review and reply to customer messages from a CRM inbox.</p></article>
                <article class="card"><h3>WhatsApp Business Cloud API</h3><p>Connect authorized WhatsApp Cloud API numbers for business messaging.</p></article>
                <article class="card"><h3>WhatsApp Embedded Signup</h3><p>Self-service API-only Embedded Signup support is implemented.</p></article>
                <article class="card"><h3>WhatsApp Coexistence readiness</h3><p>Business App + Cloud API coexistence support is implemented and may need final validation.</p></article>
                <article class="card"><h3>Facebook Messenger Pages</h3><p>Connect Facebook Pages for Messenger customer conversations.</p></article>
                <article class="card"><h3>Facebook Login and Page selection</h3><p>Facebook Login for Business with Page picker for Page connection.</p></article>
                <article class="card"><h3>Multiple connected assets</h3><p>Support multiple Pages or numbers within a tenant where configured.</p></article>
                <article class="card"><h3>Tenant-isolated data</h3><p>Each client accesses only its own data and connected assets.</p></article>
                <article class="card"><h3>Role and access management</h3><p>Control who can manage messaging settings and inbox tools.</p></article>
                <article class="card"><h3>Webhook diagnostics</h3><p>Monitor connection status and webhook processing for troubleshooting.</p></article>
            </div>
        </section>

        <section class="wrap" id="how">
            <h2 class="section-title">How businesses use the service</h2>
            <p class="section-intro">A simple path from account request to day-to-day customer messaging.</p>
            <div class="steps">
                <article class="step"><h3>Request a business account</h3><p>Contact Techno Web Masr to request a Techno Online Store workspace.</p></article>
                <article class="step"><h3>Receive a tenant workspace</h3><p>A dedicated store account is provisioned for the business.</p></article>
                <article class="step"><h3>Connect Meta assets</h3><p>Connect Facebook Pages and/or WhatsApp Business assets the client owns or manages.</p></article>
                <article class="step"><h3>Manage conversations</h3><p>Inbound customer messages appear in the CRM inbox for that tenant.</p></article>
                <article class="step"><h3>Reply from the CRM</h3><p>Authorized staff reply to customers within applicable messaging windows and policies.</p></article>
                <article class="step"><h3>Monitor activity</h3><p>Review messaging activity, connection status and diagnostics.</p></article>
            </div>
        </section>

        <section class="wrap" id="meta-data">
            <h2 class="section-title">How We Use Meta Platform Data</h2>
            <div class="panel">
                <p style="margin:0;font-family:'Segoe UI',ui-sans-serif,system-ui,sans-serif;color:var(--muted);">
                    Clients voluntarily connect their own Facebook Pages and WhatsApp Business assets.
                    Platform Data is used only to provide CRM messaging, routing, page/number connection,
                    webhook processing and customer support functionality.
                    Each client can access only its own connected assets and communications.
                    Techno Web Masr does not sell Platform Data.
                    Access tokens are stored encrypted and are never displayed publicly.
                    Central registries contain routing metadata only and no access tokens.
                </p>
                <p class="meta-note">
                    Meta products (Facebook, Messenger, WhatsApp) are third-party services. Techno Online Store does not claim Meta partnership or endorsement.
                </p>
            </div>
        </section>

        <section class="wrap" id="security">
            <h2 class="section-title">Security and privacy</h2>
            <div class="card-grid">
                <article class="card"><h3>Tenant database isolation</h3><p>Each merchant store uses an isolated tenant database context.</p></article>
                <article class="card"><h3>Encrypted access tokens</h3><p>Page and WhatsApp tokens are encrypted at rest in tenant storage.</p></article>
                <article class="card"><h3>Signed onboarding state</h3><p>Self-service onboarding uses signed/encrypted state — raw tenant IDs are not trusted.</p></article>
                <article class="card"><h3>Server-side OAuth</h3><p>Authorization code exchanges run on the server; app secrets stay off the browser.</p></article>
                <article class="card"><h3>Access controls</h3><p>Admin and tenant panels use authenticated access for operational tools.</p></article>
                <article class="card"><h3>Redacted diagnostics</h3><p>Logs minimize or redact sensitive values where applicable. There is no central operational mirror of tenant conversations.</p></article>
            </div>
            <p class="section-intro" style="margin-top:1.25rem;margin-bottom:0;">
                Legal documents:
                <a href="{{ $privacyUrl }}">Privacy Policy</a> ·
                <a href="{{ $termsUrl }}">Terms of Service</a> ·
                <a href="{{ $deletionUrl }}">Data Deletion Instructions</a>
            </p>
        </section>

        <section class="wrap" id="company">
            <h2 class="section-title">Built and operated by Techno Web Masr</h2>
            <div class="company-block">
                <div class="panel">
                    <ul>
                        <li><strong>Company:</strong> Techno Web Masr</li>
                        <li><strong>Type:</strong> Software company and SaaS / Tech Provider</li>
                        <li><strong>Services:</strong> web development, ecommerce systems, CRM, business messaging integrations and custom software</li>
                        <li><strong>Official website:</strong> <a href="{{ $companyUrl }}">https://technomasr.com</a></li>
                        <li><strong>Official contact:</strong> <a href="{{ $contactUrl }}">Contact Techno Web Masr</a></li>
                    </ul>
                </div>
                <div class="panel">
                    <h3 class="sans" style="margin:0 0 0.6rem;">Cross-links for reviewers</h3>
                    <div class="footer-nav">
                        <a href="{{ $companyProductUrl }}">Company product page</a>
                        <a href="{{ $canonicalUrl }}">Application product page</a>
                        <a href="{{ $platformUrl }}">Platform access</a>
                        <a href="{{ $contactUrl }}">Contact form</a>
                        <a href="{{ $privacyUrl }}">Privacy Policy</a>
                        <a href="{{ $termsUrl }}">Terms of Service</a>
                        <a href="{{ $deletionUrl }}">Data Deletion</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="wrap">
            <div class="cta-band">
                <div>
                    <h2>Talk with Techno Web Masr</h2>
                    <p>Request a business account or ask how Techno Online Store can support your ecommerce and messaging workflows.</p>
                </div>
                <div class="actions">
                    <a class="btn btn-primary" href="{{ $contactUrl }}">Contact Us</a>
                    <a class="btn btn-secondary" href="{{ $platformUrl }}">Access Platform</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="wrap footer-grid">
            <div>
                <h3>Techno Web Masr</h3>
                <p class="copy" style="margin-top:0;">
                    Software company and Tech Provider for Techno Online Store —
                    ecommerce, CRM and business messaging SaaS.
                </p>
            </div>
            <div>
                <h3>Product</h3>
                <nav class="footer-nav" aria-label="Product links">
                    <a href="{{ $canonicalUrl }}">Techno Online Store</a>
                    <a href="{{ $platformUrl }}">Access Platform</a>
                    <a href="{{ $companyProductUrl }}">Company product page</a>
                    <a href="{{ $companyUrl }}">Company website</a>
                    <a href="{{ $contactUrl }}">Contact</a>
                </nav>
            </div>
            <div>
                <h3>Legal</h3>
                <nav class="footer-nav" aria-label="Legal links">
                    <a href="{{ $privacyUrl }}">Privacy Policy</a>
                    <a href="{{ $termsUrl }}">Terms of Service</a>
                    <a href="{{ $deletionUrl }}">Data Deletion Instructions</a>
                </nav>
            </div>
        </div>
        <div class="wrap">
            <p class="copy">
                © {{ date('Y') }} Techno Web Masr · Techno Online Store ·
                <a href="{{ $platformUrl }}">online-store.technomasrsystems.com</a>
            </p>
        </div>
    </footer>
</body>
</html>
