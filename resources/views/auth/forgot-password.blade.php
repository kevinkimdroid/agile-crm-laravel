<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password — {{ config('branding.client_short') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset(config('branding.favicon')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.client-brand-styles')
    @include('partials.auth-card-styles')
</head>
<body class="auth-page">
    <div class="auth-panel">
        <div class="auth-card">
            <div class="auth-brand">
                @include('partials.client-brand', ['variant' => 'full'])
            </div>
            <div class="auth-card-body">
                <h1 class="auth-title">Forgot your password?</h1>
                <p class="auth-subtitle">Enter your email and we'll send you a reset link.</p>

                @if(session('status'))
                    <div class="alert alert-success py-3 mb-3"><i class="bi bi-check-circle me-2"></i>{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-3 mb-3">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        @foreach($errors->all() as $error) {{ $error }} @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="auth-label">Email address</label>
                        <input type="email" name="email" class="auth-input" value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
                    </div>
                    <button type="submit" class="btn auth-btn">Send reset link</button>
                </form>

                <a href="{{ route('login') }}" class="auth-back"><i class="bi bi-arrow-left"></i> Back to sign in</a>
            </div>
        </div>
    </div>
</body>
</html>
