<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Techno Web Masr</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #0f766e;
            --link: #0f766e;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.65;
        }
        a { color: var(--link); }
        .site-header, .site-footer {
            border-bottom: 1px solid var(--border);
            background: var(--card);
        }
        .site-footer { border-bottom: 0; border-top: 1px solid var(--border); margin-top: 3rem; }
        .inner {
            max-width: 48rem;
            margin: 0 auto;
            padding: 1.25rem 1.25rem;
        }
        .brand {
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--text);
            text-decoration: none;
        }
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem 1.25rem;
            margin-top: 0.75rem;
            font-size: 0.95rem;
        }
        .nav a { text-decoration: none; color: var(--muted); }
        .nav a:hover, .nav a.active { color: var(--accent); }
        main.inner { padding-top: 2rem; padding-bottom: 2rem; }
        h1 { margin: 0 0 0.5rem; font-size: 1.85rem; line-height: 1.25; }
        .meta { color: var(--muted); margin: 0 0 1.75rem; font-size: 0.95rem; }
        h2 { margin: 1.75rem 0 0.6rem; font-size: 1.2rem; }
        p, li { color: #1e293b; }
        ul { padding-inline-start: 1.25rem; }
        li + li { margin-top: 0.35rem; }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem 1.35rem;
        }
        .footer-copy { color: var(--muted); font-size: 0.875rem; margin: 0 0 0.75rem; }
        .footer-nav { display: flex; flex-wrap: wrap; gap: 0.75rem 1.25rem; font-size: 0.9rem; }
        .footer-nav a { text-decoration: none; color: var(--muted); }
        .footer-nav a:hover { color: var(--accent); }
        code { font-size: 0.9em; background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="inner">
            <a class="brand" href="{{ url('/privacy-policy') }}">Techno Web Masr</a>
            <nav class="nav" aria-label="Legal">
                <a href="{{ route('legal.privacy') }}" @class(['active' => request()->routeIs('legal.privacy')])>Privacy Policy</a>
                <a href="{{ route('legal.terms') }}" @class(['active' => request()->routeIs('legal.terms')])>Terms of Service</a>
                <a href="{{ route('legal.data-deletion') }}" @class(['active' => request()->routeIs('legal.data-deletion')])>Data Deletion</a>
            </nav>
        </div>
    </header>

    <main class="inner">
        <article class="card">
            @yield('content')
        </article>
    </main>

    <footer class="site-footer">
        <div class="inner">
            <p class="footer-copy">
                © {{ date('Y') }} Techno Web Masr ·
                <a href="https://online-store.technomasrsystems.com">online-store.technomasrsystems.com</a> ·
                <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
            </p>
            <nav class="footer-nav" aria-label="Footer">
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}">Terms of Service</a>
                <a href="{{ route('legal.data-deletion') }}">Data Deletion</a>
            </nav>
        </div>
    </footer>
</body>
</html>
