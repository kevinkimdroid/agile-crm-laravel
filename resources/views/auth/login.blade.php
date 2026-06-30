<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — {{ config('branding.client_short') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset(config('branding.favicon')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" href="{{ asset('images/login-hero.jpg') }}?v=4" as="image">
    @include('partials.client-brand-styles')
    <style>
        :root {
            --brand-navy: {{ config('branding.primary') }};
            --brand-navy-dark: {{ config('branding.primary_dark') }};
            --brand-red: {{ config('branding.accent') }};
            --brand-logo-bg: {{ config('branding.logo_bg') }};
            --brand-gray: #64748b;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            font-family: 'Source Sans 3', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .login-page {
            height: 100vh;
            max-height: 100dvh;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .login-bg-img {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .login-bg-img img {
            width: 100%;
            height: 100%;
            min-width: 100%;
            min-height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
        }
        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                105deg,
                rgba(255, 255, 255, 0.96) 0%,
                rgba(248, 250, 252, 0.92) 32%,
                rgba(241, 245, 249, 0.72) 48%,
                rgba(18, 41, 82, 0.28) 62%,
                rgba(18, 41, 82, 0.52) 100%
            );
            pointer-events: none;
            z-index: 1;
        }
        .login-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding: 1.5rem 2rem 4.5rem 3rem;
            position: relative;
            z-index: 2;
            max-width: 520px;
            min-height: 0;
            overflow-y: auto;
        }
        @media (max-width: 768px) {
            .login-left { padding: 1rem 1.25rem; max-width: 100%; }
            .login-page::before {
                background: linear-gradient(
                    180deg,
                    rgba(255, 255, 255, 0.97) 0%,
                    rgba(248, 250, 252, 0.94) 55%,
                    rgba(18, 41, 82, 0.45) 100%
                );
            }
            .login-tagline { width: 280px; height: 180px; }
            .login-tagline span {
                bottom: 40px;
                right: 16px;
                max-width: 200px;
                font-size: 0.85rem;
            }
        }
        .login-left-inner {
            width: 100%;
            max-width: 420px;
            flex-shrink: 0;
        }

        .login-tagline {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 360px;
            height: 220px;
            z-index: 2;
        }
        .login-tagline-shape {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 0 200px 340px;
            border-color: transparent transparent var(--brand-navy) transparent;
        }
        .login-tagline-shape-accent {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 0 140px 240px;
            border-color: transparent transparent var(--brand-red) transparent;
        }
        .login-tagline span {
            position: absolute;
            bottom: 52px;
            right: 28px;
            max-width: 240px;
            font-size: 0.95rem;
            font-weight: 700;
            font-style: italic;
            letter-spacing: 0.03em;
            line-height: 1.45;
            color: #fff;
            text-align: right;
            display: block;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            background: #fff;
            border-radius: 20px;
            box-shadow:
                0 1px 2px rgba(15, 39, 68, 0.04),
                0 24px 56px -12px rgba(15, 39, 68, 0.14),
                0 0 0 1px rgba(226, 232, 240, 0.8);
            padding: 2rem 2.25rem 1.75rem;
            animation: cardFade 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes cardFade {
            from { opacity: 0; transform: translateY(16px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-brand {
            margin-bottom: 1.5rem;
            padding-bottom: 1.35rem;
            border-bottom: 1px solid #e8edf3;
        }
        .login-brand .ko-brand--full { justify-content: center; }
        .login-heading {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--brand-navy);
            margin: 0 0 1.25rem;
            letter-spacing: -0.01em;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        .login-input {
            width: 100%;
            padding: 0.78rem 1rem;
            font-size: 0.95rem;
            color: #1a1a1a;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .login-input:hover { border-color: #cbd5e1; background: #fff; }
        .login-input:focus {
            outline: none;
            border-color: var(--brand-navy);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(27, 63, 122, 0.12);
        }
        .login-input::placeholder { color: #94a3b8; }
        .btn-signin {
            width: 100%;
            padding: 0.9rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            background: var(--brand-navy);
            border: none;
            border-radius: 10px;
            margin-top: 1.25rem;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 10px rgba(27, 63, 122, 0.25);
        }
        .btn-signin:hover {
            background: var(--brand-navy-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(27, 63, 122, 0.3);
            color: #fff;
        }
        .login-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.35rem;
            border-top: 1px solid #eef2f6;
        }
        .login-forgot {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--brand-navy);
            text-decoration: none;
        }
        .login-forgot:hover { color: var(--brand-red); }
        .login-secure {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            color: var(--brand-gray);
            font-weight: 600;
        }
        .login-secure i { color: var(--brand-navy); }
        .alert-danger {
            border-radius: 10px;
            border: 1px solid rgba(185, 28, 28, 0.2);
            background: rgba(185, 28, 28, 0.08);
            color: #991b1b;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .login-statements {
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
            padding: 1.25rem 1rem;
            background: rgba(255, 255, 255, 0.94);
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 10px 40px -12px rgba(15, 39, 68, 0.18);
            backdrop-filter: blur(8px);
        }
        @media (max-width: 600px) {
            .login-statements { grid-template-columns: 1fr; gap: 1.25rem; }
        }
        .login-statements .stmt {
            padding: 0.5rem 0.35rem;
            text-align: center;
            border-right: 1px solid rgba(148, 163, 184, 0.35);
        }
        .login-statements .stmt:last-child { border-right: none; }
        @media (max-width: 600px) {
            .login-statements .stmt {
                border-right: none;
                border-bottom: 1px solid rgba(148, 163, 184, 0.35);
                padding-bottom: 1rem;
            }
            .login-statements .stmt:last-child { border-bottom: none; }
        }
        .login-statements .stmt h4 {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--brand-navy-dark);
            margin: 0 0 0.45rem;
        }
        .login-statements .stmt p,
        .login-statements .stmt ul {
            font-size: 0.8rem;
            color: #334155;
            line-height: 1.5;
            margin: 0;
            font-weight: 500;
        }
        .login-statements .stmt ul {
            list-style: none;
            padding: 0;
            text-align: left;
            display: inline-block;
        }
        .login-statements .stmt ul li::before {
            content: '•';
            color: var(--brand-red);
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .login-social {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.25rem;
        }
        .login-social a {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--brand-navy);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.25s;
            box-shadow: 0 2px 8px rgba(27, 63, 122, 0.25);
        }
        .login-social a:hover {
            background: var(--brand-red);
            transform: translateY(-3px);
        }

        .login-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0.65rem 1.5rem;
            background: rgba(255, 255, 255, 0.98);
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            z-index: 3;
            border-top: 1px solid rgba(148, 163, 184, 0.35);
            text-align: center;
            box-shadow: 0 -4px 20px rgba(15, 39, 68, 0.08);
        }
        .login-footer a { color: var(--brand-navy-dark); font-weight: 700; text-decoration: none; }
        .login-footer a:hover { color: var(--brand-red); }

        @media (prefers-reduced-motion: reduce) {
            .login-card { animation: none; }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-bg-img">
            <img src="{{ asset('images/login-hero.jpg') }}?v=4" alt="" width="1920" height="1080" loading="eager" fetchpriority="high">
        </div>
        <div class="login-left">
            <div class="login-left-inner">
                <div class="login-card">
                    <div class="login-brand">
                        @include('partials.client-brand', ['variant' => 'full'])
                    </div>

                    <h1 class="login-heading">Sign in</h1>

                    @if ($errors->any())
                            <div class="alert alert-danger py-3 mb-3">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                @foreach ($errors->all() as $error) {{ $error }} @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="user_name">Username</label>
                                <input type="text" name="user_name" id="user_name" class="login-input" value="{{ old('user_name') }}" placeholder="Enter your username" required autofocus>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="password">Password</label>
                                <input type="password" name="password" id="password" class="login-input" placeholder="Enter your password" required>
                            </div>
                            <button type="submit" class="btn btn-signin" id="loginBtn">Sign in</button>
                        </form>

                        <div class="login-card-footer">
                            <a href="{{ route('password.request') }}" class="login-forgot">Forgot password?</a>
                            <div class="login-secure">
                                <i class="bi bi-shield-lock-fill"></i>
                                <span>Secure login</span>
                            </div>
                        </div>
                </div>

                <div class="login-statements">
                    <div class="stmt">
                        <h4>Integrity</h4>
                        <p>No compromise on trust and transparency.</p>
                    </div>
                    <div class="stmt">
                        <h4>Excellence</h4>
                        <p>Perfecting what we do for every customer.</p>
                    </div>
                    <div class="stmt">
                        <h4>Core Values</h4>
                        <ul>
                            <li>Customer driven</li>
                            <li>Innovative</li>
                            <li>Team work</li>
                        </ul>
                    </div>
                </div>

                <div class="login-social">
                    @if(config('branding.social.facebook'))
                    <a href="{{ config('branding.social.facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    @endif
                    @if(config('branding.social.instagram'))
                    <a href="{{ config('branding.social.instagram') }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    @endif
                    @if(config('branding.social.twitter'))
                    <a href="{{ config('branding.social.twitter') }}" target="_blank" rel="noopener" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                    @endif
                </div>
            </div>
        </div>

        <div class="login-tagline">
            <div class="login-tagline-shape"></div>
            <div class="login-tagline-shape-accent"></div>
            <span>{{ config('branding.tagline') }}</span>
        </div>

        <footer class="login-footer">
            {{ config('branding.platform_footer') }} © {{ date('Y') }} · <a href="#">Privacy Policy</a>
        </footer>
    </div>

    <script>
    document.getElementById('loginForm')?.addEventListener('submit', function() {
        var btn = document.getElementById('loginBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Signing in...'; }
    });
    </script>
</body>
</html>
