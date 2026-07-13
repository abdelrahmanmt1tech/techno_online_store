<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('dashboard.messenger_connect'))</title>
    <style>
        :root {
            --bg: #f4f6f8;
            --card: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --border: #e5e7eb;
            --accent: #2563eb;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        .wrap {
            max-width: 42rem;
            margin: 0 auto;
            padding: 2.5rem 1.25rem;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }
        h1 { margin: 0 0 0.5rem; font-size: 1.5rem; }
        .muted { color: var(--muted); margin: 0 0 1.25rem; }
        .badge {
            display: inline-block;
            background: color-mix(in srgb, var(--accent) 12%, white);
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            margin-bottom: 1rem;
        }
        .actions { margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem; }
        a.button, button.button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            background: var(--accent);
            color: white;
            border: 0;
            cursor: pointer;
            font-size: 1rem;
        }
        a.button.secondary, button.button.secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }
        button.button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }
        .page-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.75rem; }
        .page-item {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.85rem 1rem;
        }
        .page-item label { flex: 1; cursor: pointer; }
        .page-meta { color: var(--muted); font-size: 0.875rem; margin-top: 0.2rem; }
        .connected { color: var(--accent); font-size: 0.75rem; font-weight: 700; }
        code { font-size: 0.875rem; background: #f3f4f6; padding: 0.1rem 0.35rem; border-radius: 0.25rem; }
        dl { margin: 0; display: grid; gap: 0.75rem; }
        dt { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
        dd { margin: 0.15rem 0 0; font-weight: 600; word-break: break-all; }
    </style>
</head>
<body>
    <div class="wrap">
        @yield('content')
    </div>
</body>
</html>
