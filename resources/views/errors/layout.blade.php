<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') | {{ config('app-brand.name') }}</title>
        <style>
            :root { color-scheme: dark; }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #011d12;
                color: rgba(255, 255, 255, 0.92);
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
                text-align: center;
                padding: 2rem;
            }
            .card { max-width: 30rem; }
            .badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 3.25rem;
                height: 3.25rem;
                border-radius: 14px;
                background: linear-gradient(145deg, #005237, #00875f);
                font-size: 0.85rem;
                font-weight: 800;
                letter-spacing: 0.05em;
                margin: 0 auto 1.5rem;
            }
            .code {
                font-size: 0.85rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: rgba(0, 191, 137, 0.85);
                margin: 0 0 0.5rem;
            }
            h1 { font-size: 1.6rem; margin: 0 0 0.75rem; }
            p { color: rgba(255, 255, 255, 0.6); line-height: 1.5; margin: 0 0 1.75rem; }
            a.cta {
                display: inline-block;
                padding: 0.65rem 1.5rem;
                border-radius: 0.6rem;
                background: rgba(0, 135, 95, 0.2);
                border: 1px solid rgba(0, 135, 95, 0.45);
                color: #fff;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="badge">{{ config('app-brand.short_name') }}</div>
            <p class="code">@yield('code')</p>
            <h1>@yield('heading')</h1>
            <p>@yield('message')</p>
            <a class="cta" href="@yield('cta_href', '/')">@yield('cta_label', 'Back to safety')</a>
        </div>
    </body>
</html>
