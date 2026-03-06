<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Guide in Morocco') }} | Backend API</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --surface: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --line: #e2e8f0;
            --primary: #0f766e;
            --primary-soft: #ccfbf1;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Arial, sans-serif;
            background: radial-gradient(circle at top right, #dbeafe 0%, var(--bg) 45%);
            color: var(--text);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 56px;
        }

        .hero {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 18px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--primary);
            background: var(--primary-soft);
            border-radius: 999px;
            padding: 6px 10px;
            margin-bottom: 12px;
        }

        h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.2;
        }

        .subtitle {
            margin-top: 10px;
            color: var(--muted);
            font-size: 16px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 18px;
        }

        .card p,
        .card li {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        ul,
        ol {
            margin: 0;
            padding-left: 18px;
        }

        a {
            color: #0369a1;
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }

        .footer {
            margin-top: 14px;
            font-size: 13px;
            color: var(--muted);
        }

        code {
            background: #f1f5f9;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 2px 6px;
            font-size: 12px;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="container">
        <section class="hero">
            <span class="badge">Guide in Morocco</span>
            <h1>Backend API</h1>
            <p class="subtitle">
                Central API for guides, tours, bookings, and reviews. Use this service for registration,
                discovery, and authenticated operations.
            </p>
        </section>

        <section class="grid">
            <article class="card">
                <h2>Public Endpoints</h2>
                <ul>
                    <li><a href="{{ url('/api/lookups') }}" target="_blank">GET /api/lookups</a></li>
                    <li><a href="{{ url('/api/guides') }}" target="_blank">GET /api/guides</a></li>
                    <li><a href="{{ url('/api/tours') }}" target="_blank">GET /api/tours</a></li>
                    <li><a href="{{ url('/api/cities') }}" target="_blank">GET /api/cities</a></li>
                    <li><a href="{{ url('/api/languages') }}" target="_blank">GET /api/languages</a></li>
                </ul>
            </article>

            <article class="card">
                <h2>Core Modules</h2>
                <ul>
                    <li>Guide registration and admin activation flow</li>
                    <li>Tours with type, difficulty, city, and currency</li>
                    <li>Bookings and review management</li>
                    <li>Guide documents and profile metadata</li>
                    <li>Lookup data for frontend onboarding</li>
                </ul>
            </article>

            <article class="card">
                <h2>Authentication</h2>
                <ol>
                    <li>Register via <code>POST /api/register</code></li>
                    <li>Login via <code>POST /api/login</code></li>
                    <li>Call protected routes with Bearer token</li>
                </ol>
                <p style="margin-top: 10px;">
                    Current environment: <strong>{{ config('app.env') }}</strong>
                </p>
            </article>
        </section>

        <p class="footer">
            If you are seeing this page, the backend is up. API base path: <code>/api</code>
        </p>
    </div>
</body>
</html>