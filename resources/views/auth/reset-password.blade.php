<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password — {{ config('branding.client_short') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset(config('branding.favicon')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.client-brand-styles')
    @include('partials.auth-card-styles')
    <style>
        .auth-username {
            font-size: 0.88rem;
            color: var(--brand-navy);
            background: rgba(27, 63, 122, 0.06);
            padding: 0.55rem 0.85rem;
            border-radius: 8px;
            border-left: 3px solid var(--brand-red);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-panel">
        <div class="auth-card">
            <div class="auth-brand">
                @include('partials.client-brand', ['variant' => 'full'])
            </div>
            <div class="auth-card-body">
                <h1 class="auth-title">Set new password</h1>
                @if($user_name ?? null)
                    <p class="auth-username mb-2"><i class="bi bi-person-fill me-1"></i> Resetting for <strong>{{ $user_name }}</strong></p>
                @endif
                <p class="auth-subtitle">Choose a new password — at least 8 characters.</p>

                @if($errors->any())
                    <div class="alert alert-danger py-3 mb-3">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        @foreach($errors->all() as $error) {{ $error }} @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">
                    <div class="mb-3">
                        <label class="auth-label">New password</label>
                        <input type="password" name="password" class="auth-input" required autofocus minlength="8" placeholder="Enter new password">
                    </div>
                    <div class="mb-3">
                        <label class="auth-label">Confirm password</label>
                        <input type="password" name="password_confirmation" class="auth-input" required minlength="8" placeholder="Confirm new password">
                    </div>
                    <button type="submit" class="btn auth-btn">Reset password</button>
                </form>

                <a href="{{ route('login') }}" class="auth-back"><i class="bi bi-arrow-left"></i> Back to sign in</a>
            </div>
        </div>
    </div>
</body>
</html>
