<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@700&family=Source+Sans+3:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
    .ko-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-align: left;
        line-height: 1.15;
    }
    .ko-brand-mark {
        flex-shrink: 0;
        line-height: 0;
    }
    .ko-brand-mark svg {
        display: block;
        width: 100%;
        height: 100%;
    }
    .ko-brand-line1 {
        font-family: 'Libre Baskerville', Georgia, 'Times New Roman', serif;
        font-weight: 700;
        font-size: 0.92rem;
        color: {{ config('branding.primary') }};
        letter-spacing: 0.04em;
        line-height: 1.15;
    }
    .ko-brand-line2 {
        font-family: 'Libre Baskerville', Georgia, 'Times New Roman', serif;
        font-weight: 700;
        font-size: 0.74rem;
        color: {{ config('branding.accent') }};
        letter-spacing: 0.05em;
        margin-top: 0.12rem;
        line-height: 1.15;
    }
    .ko-brand-tagline {
        font-family: 'Source Sans 3', system-ui, sans-serif;
        font-size: 0.68rem;
        font-style: italic;
        font-weight: 400;
        color: #64748b;
        margin-top: 0.28rem;
        letter-spacing: 0.01em;
    }

    /* Sidebar — compact but readable */
    .ko-brand--compact .ko-brand-mark { width: 46px; height: 46px; }
    .ko-brand--compact .ko-brand-line1 { font-size: 0.78rem; letter-spacing: 0.03em; }
    .ko-brand--compact .ko-brand-line2 { font-size: 0.62rem; letter-spacing: 0.04em; }
    .ko-brand--compact .ko-brand-tagline { display: none; }

    /* Login / auth — full presentation */
    .ko-brand--full {
        justify-content: flex-start;
        gap: 0.9rem;
    }
    .ko-brand--full .ko-brand-mark { width: 58px; height: 58px; }
    .ko-brand--full .ko-brand-line1 { font-size: 1.05rem; }
    .ko-brand--full .ko-brand-line2 { font-size: 0.82rem; }
    .ko-brand--full .ko-brand-tagline { font-size: 0.72rem; }
</style>
