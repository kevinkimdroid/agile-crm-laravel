<style>
    :root {
        --brand-navy: {{ config('branding.primary') }};
        --brand-navy-dark: {{ config('branding.primary_dark') }};
        --brand-red: {{ config('branding.accent') }};
    }
    *, *::before, *::after { box-sizing: border-box; }
    html, body {
        margin: 0;
        padding: 0;
        min-height: 100%;
        font-family: 'Source Sans 3', system-ui, sans-serif;
        -webkit-font-smoothing: antialiased;
    }
    .auth-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.25rem;
        background: linear-gradient(145deg, #eef1f6 0%, #f4f6f9 50%, #e8edf3 100%);
    }
    .auth-panel { width: 100%; max-width: 440px; }
    .auth-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 20px 48px -16px rgba(15, 39, 68, 0.18);
        padding: 2rem 2rem 1.75rem;
    }
    .auth-brand {
        margin-bottom: 1.5rem;
        padding-bottom: 1.35rem;
        border-bottom: 1px solid #e8edf3;
    }
    .auth-brand .ko-brand--full { justify-content: center; }
    .auth-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: var(--brand-navy);
        margin: 0 0 0.35rem;
    }
    .auth-subtitle {
        font-size: 0.92rem;
        color: #64748b;
        margin-bottom: 1.5rem;
        line-height: 1.55;
    }
    .auth-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.4rem;
    }
    .auth-input {
        width: 100%;
        padding: 0.78rem 1rem;
        font-size: 0.95rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .auth-input:focus {
        outline: none;
        border-color: var(--brand-navy);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(27, 63, 122, 0.12);
    }
    .auth-btn {
        width: 100%;
        margin-top: 0.5rem;
        padding: 0.9rem 1.5rem;
        font-weight: 600;
        color: #fff;
        background: var(--brand-navy);
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(27, 63, 122, 0.25);
        transition: background 0.2s, transform 0.2s;
    }
    .auth-btn:hover {
        background: var(--brand-navy-dark);
        transform: translateY(-1px);
        color: #fff;
    }
    .auth-back {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin-top: 1.35rem;
        color: var(--brand-navy);
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
    }
    .auth-back:hover { color: var(--brand-red); }
    .alert { border-radius: 10px; }
</style>
