<style>
    .sms-desk-page {
        max-width: 1280px;
        --ko-navy: #202665;
        --ko-navy-soft: #1B3F7A;
        --ko-red: #D30E13;
        --ko-wash: #f4f6fb;
        --ko-line: #e2e6f0;
    }
    .sms-desk {
        background: #fff;
        border: 1px solid var(--ko-line);
        border-radius: 18px;
        overflow: hidden;
        min-height: calc(100vh - 8rem);
        display: flex;
        flex-direction: column;
        box-shadow: 0 10px 40px rgba(32, 38, 101, 0.08);
    }
    .sms-queue table { width: 100%; margin: 0; }
    .sms-queue td { padding: 0; border: 0; }
    .sms-item.is-active { background: #fff; box-shadow: inset 3px 0 0 var(--ko-red); }
    .sms-send-btn {
        background: var(--ko-navy); border: 0; color: #fff; font-weight: 700;
        border-radius: 10px; padding: 0.65rem 1.2rem; white-space: nowrap;
    }
    .sms-send-btn:hover { background: var(--ko-red); color: #fff; }
    .sms-send-btn:disabled { opacity: 0.45; }
    .sms-dock-grow { margin-left: auto; display: flex; flex-wrap: wrap; align-items: end; gap: 0.75rem; }
    .sms-count { font-size: 0.8rem; color: rgba(255,255,255,0.8); }
    .sms-table { margin: 0; }
    .sms-table tbody tr:hover { background: var(--ko-wash); }
    .sms-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.6rem; padding: 0.75rem 1rem; border-bottom: 1px solid var(--ko-line); }
    .sms-filters a span { font-weight: 500; color: inherit; opacity: 0.75; margin-left: 0.25rem; }
    .sms-filters a.is-on span { opacity: 1; color: inherit; }
    .sms-desk-bar {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.85rem;
        padding: 0.85rem 1.1rem;
        background: var(--ko-navy);
        color: #fff;
    }
    .sms-desk-brand {
        display: flex; align-items: center; gap: 0.55rem; font-weight: 700; letter-spacing: -0.02em;
    }
    .sms-desk-brand i {
        width: 2rem; height: 2rem; border-radius: 8px; background: var(--ko-red);
        display: inline-flex; align-items: center; justify-content: center; color: #fff;
    }
    .sms-seg {
        display: flex; background: rgba(255,255,255,0.1); border-radius: 999px; padding: 0.2rem;
        margin-left: auto;
    }
    .sms-seg a {
        color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.82rem; font-weight: 600;
        padding: 0.35rem 0.9rem; border-radius: 999px;
    }
    .sms-seg a.is-on { background: #fff; color: var(--ko-navy); }
    .sms-dots { display: flex; gap: 0.75rem; font-size: 0.75rem; color: rgba(255,255,255,0.75); }
    .sms-dot { display: inline-flex; align-items: center; gap: 0.3rem; }
    .sms-dot::before {
        content: ''; width: 0.5rem; height: 0.5rem; border-radius: 50%; background: var(--ko-red);
    }
    .sms-dot.on::before { background: #86efac; }
    .sms-desk-body { display: grid; flex: 1; min-height: 0; }
    @media (min-width: 992px) {
        .sms-desk-body.split { grid-template-columns: 22rem minmax(0, 1fr); }
    }
    .sms-queue { border-right: 1px solid var(--ko-line); background: var(--ko-wash); min-height: 28rem; display: flex; flex-direction: column; }
    .sms-queue-head { padding: 0.75rem; border-bottom: 1px solid var(--ko-line); }
    .sms-queue-list { overflow: auto; flex: 1; max-height: min(70vh, 760px); }
    .sms-item {
        display: block; width: 100%; text-align: left; border: 0; background: transparent;
        padding: 0.85rem 0.9rem; border-bottom: 1px solid var(--ko-line); cursor: pointer;
    }
    .sms-item:hover { background: #e8ecf6; }
    .sms-item.is-on { background: #fff; box-shadow: inset 3px 0 0 var(--ko-red); }
    .sms-item-top { display: flex; justify-content: space-between; gap: 0.5rem; font-weight: 650; font-size: 0.9rem; color: #0f172a; }
    .sms-item-meta { font-size: 0.72rem; color: #64748b; margin-top: 0.15rem; }
    .sms-item-preview { font-size: 0.8rem; color: #475569; margin-top: 0.25rem; }
    .sms-read { display: flex; flex-direction: column; min-width: 0; background: #fff; }
    .sms-read-main { flex: 1; padding: 1.5rem 1.6rem 1rem; }
    .sms-read-kicker { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--ko-red); }
    .sms-read-to { font-size: 1.35rem; font-weight: 700; color: var(--ko-navy); margin: 0.2rem 0 0.5rem; }
    .sms-read-meta { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.1rem; }
    .sms-pill { font-size: 0.75rem; background: #eef0f8; color: var(--ko-navy); border-radius: 999px; padding: 0.2rem 0.6rem; }
    .sms-bubble {
        max-width: 36rem; background: var(--ko-wash); border: 1px solid var(--ko-line);
        border-radius: 4px 18px 18px 18px; padding: 1rem 1.1rem;
        font-size: 0.95rem; line-height: 1.55; color: #1e293b; white-space: pre-wrap; word-break: break-word;
    }
    .sms-dock {
        margin-top: auto; padding: 0.9rem 1.25rem;
        border-top: 1px solid var(--ko-line); background: var(--ko-wash);
        display: flex; flex-wrap: wrap; align-items: end; gap: 0.75rem;
    }
    .sms-dock .form-control { max-width: 7rem; }
    .sms-quick { display: flex; gap: 0.3rem; flex-wrap: wrap; }
    .sms-quick button {
        border: 1px solid var(--ko-line); background: #fff; border-radius: 8px;
        padding: 0.3rem 0.55rem; font-size: 0.75rem; font-weight: 600; color: var(--ko-navy);
    }
    .sms-quick button.is-on, .sms-quick button:hover { background: var(--ko-navy); color: #fff; border-color: var(--ko-navy); }
    .sms-empty { text-align: center; padding: 3rem 1.5rem; color: #64748b; }
    .sms-empty i { font-size: 2.4rem; color: var(--ko-navy); opacity: 0.45; }
    .sms-setup { max-width: 28rem; margin: 0 auto; text-align: left; background: #fff7f7; border: 1px solid #fecaca; border-radius: 12px; padding: 1rem 1.1rem; }
    .sms-setup ol { margin: 0.4rem 0 0; padding-left: 1.15rem; font-size: 0.85rem; color: #57534e; }
    .sms-filters { display: flex; flex-wrap: wrap; gap: 0.4rem; padding: 0.75rem 1rem; border-bottom: 1px solid var(--ko-line); background: var(--ko-wash); }
    .sms-filters a {
        text-decoration: none; color: var(--ko-navy); font-size: 0.8rem; font-weight: 600;
        border: 1px solid var(--ko-line); background: #fff; border-radius: 999px; padding: 0.3rem 0.75rem;
    }
    .sms-filters a.is-on { background: var(--ko-navy); border-color: var(--ko-navy); color: #fff; }
    .sms-table-wrap { overflow: auto; max-height: min(70vh, 820px); }
    .sms-table thead th {
        position: sticky; top: 0; background: var(--ko-wash); font-size: 0.7rem; text-transform: uppercase;
        letter-spacing: 0.04em; color: #64748b; z-index: 1;
    }
    .sms-table tbody tr.filtered-out { display: none; }
    .sms-result { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
    .sms-result div { background: #fff; border: 1px solid var(--ko-line); border-radius: 10px; padding: 0.65rem; text-align: center; }
    @media (max-width: 767.98px) {
        .sms-result { grid-template-columns: 1fr 1fr; }
        .sms-desk-bar { padding-bottom: 0.7rem; }
        .sms-seg { margin-left: 0; width: 100%; }
        .sms-seg a { flex: 1; text-align: center; }
    }
</style>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/tools/partials/sms-desk-styles.blade.php ENDPATH**/ ?>