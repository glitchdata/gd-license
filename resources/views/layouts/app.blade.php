<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'GD Login')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            font-family: 'Space Grotesk', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --bg: #f5f7ff;
            --panel: #ffffff;
            --panel-alt: #0b1b3f;
            --text: #0f172a;
            --muted: #6b7280;
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --error: #dc2626;
            --success: #16a34a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.18), transparent 45%),
                radial-gradient(circle at 80% 0%, rgba(22, 163, 74, 0.15), transparent 40%),
                var(--bg);
            color: var(--text);
        }
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 4rem 1.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .hero h1 {
            margin: 0.4rem 0;
            font-size: clamp(2.4rem, 4vw, 3.6rem);
        }
        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 0.78rem;
            color: var(--muted);
        }
        .lead { color: var(--muted); }
        .card {
            background: var(--panel);
            border-radius: 1.2rem;
            padding: 2rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
        }
        .card.alt {
            background: var(--panel-alt);
            color: #fff;
            box-shadow: none;
        }
        form { display: flex; flex-direction: column; gap: 1rem; }
        label span { display: block; margin-bottom: 0.35rem; font-size: 0.9rem; }
        input {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.15);
            border-radius: 0.9rem;
            padding: 0.85rem 1rem;
            font-size: 1rem;
        }
        .card.alt input {
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }
        button {
            border: none;
            border-radius: 999px;
            padding: 0.9rem 1.2rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.35);
        }
        .banner {
            border-radius: 1rem;
            padding: 1rem 1.25rem;
        }
        .banner.success {
            background: rgba(22, 163, 74, 0.12);
            color: var(--success);
        }
        .banner.error {
            background: rgba(220, 38, 38, 0.12);
            color: var(--error);
        }
        .banner ul { margin: 0; padding-left: 1rem; }
        .status { margin: 0; }
        .link { color: var(--primary); font-weight: 600; text-decoration: none; }
        .link:hover { text-decoration: underline; }
        .stack { display: grid; gap: 1.5rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
        .details { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
        .details dt {
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-size: 0.7rem;
            color: rgba(15, 23, 42, 0.65);
        }
        .details dd { margin: 0.25rem 0 0; font-size: 1.1rem; }
    </style>
</head>
<body>
    <div class="page">
        @if (session('status'))
            <div class="banner success">
                <p class="status">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="banner error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
