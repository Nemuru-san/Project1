<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') — @yield('title')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f8fafc; color: #18181b; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        main { width: min(100%, 560px); text-align: center; }
        .code { margin: 0 0 8px; color: #71717a; font-size: 14px; font-weight: 700; letter-spacing: .16em; }
        h1 { margin: 0; font-size: clamp(28px, 6vw, 42px); line-height: 1.15; }
        p { margin: 16px auto 0; max-width: 480px; color: #52525b; font-size: 16px; line-height: 1.7; }
        a { display: inline-block; margin-top: 28px; border-radius: 10px; background: #18181b; padding: 11px 18px; color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; }
        @media (prefers-color-scheme: dark) {
            body { background: #09090b; color: #fafafa; }
            p, .code { color: #a1a1aa; }
            a { background: #fafafa; color: #18181b; }
        }
    </style>
</head>
<body>
    <main>
        <div class="code">ERROR @yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Kembali ke halaman utama</a>
    </main>
</body>
</html>
