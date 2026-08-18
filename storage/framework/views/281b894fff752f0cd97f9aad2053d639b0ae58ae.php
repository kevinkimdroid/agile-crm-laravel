<style>
    .ko-grid {
        --ko-navy: #202665;
        --ko-red: #D30E13;
        --ko-link: #202665;
        --ko-line: #e1e1e1;
        --ko-muted: #616161;
        --ko-head: #fafafa;
        margin: -1.5rem -1.75rem 0;
        background: #fff;
        min-height: calc(100vh - 4.5rem);
        display: flex;
        flex-direction: column;
        font-size: 14px;
    }
    .ko-grid-cmd {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 0.75rem;
        padding: 0.55rem 1rem; border-bottom: 1px solid var(--ko-line); background: #fff;
    }
    .ko-grid-back {
        width: 2rem; height: 2rem; border: 0; background: transparent; color: #242424;
        display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;
        text-decoration: none;
    }
    .ko-grid-back:hover { background: #f3f2f1; color: #242424; }
    .ko-grid-view { position: relative; }
    .ko-grid-view-btn {
        border: 0; background: transparent; font-size: 1.15rem; font-weight: 600;
        color: #242424; padding: 0.15rem 0.2rem; display: inline-flex; align-items: center; gap: 0.35rem;
    }
    .ko-grid-view-btn:hover { background: #f3f2f1; border-radius: 4px; }
    .ko-grid-actions { margin-left: auto; display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem; }
    .ko-grid-btn {
        border: 1px solid #d1d1d1; background: #fff; color: #242424; font-size: 0.82rem; font-weight: 600;
        padding: 0.38rem 0.7rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.35rem;
        text-decoration: none;
    }
    .ko-grid-btn:hover { background: #f3f2f1; color: #242424; }
    .ko-grid-btn.is-on { background: #edebe9; }
    .ko-grid-primary {
        background: var(--ko-navy); border-color: var(--ko-navy); color: #fff;
        font-size: 0.82rem; font-weight: 600; padding: 0.38rem 0.75rem; border-radius: 4px;
        display: inline-flex; align-items: center; gap: 0.35rem;
    }
    .ko-grid-primary:hover { background: var(--ko-red); border-color: var(--ko-red); color: #fff; }
    .ko-grid-primary:disabled { opacity: 0.45; }
    .ko-grid-searchbar {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem;
        padding: 0.65rem 1rem; border-bottom: 1px solid var(--ko-line);
    }
    .ko-grid-search {
        position: relative; flex: 1 1 18rem; max-width: 28rem;
    }
    .ko-grid-search input {
        width: 100%; border: 1px solid #8a8886; border-radius: 2px; padding: 0.4rem 2.2rem 0.4rem 0.7rem;
        font-size: 0.88rem;
    }
    .ko-grid-search input:focus { outline: 2px solid var(--ko-navy); outline-offset: -1px; border-color: var(--ko-navy); }
    .ko-grid-search .ko-grid-search-ico {
        position: absolute; right: 0.55rem; top: 50%; transform: translateY(-50%); color: var(--ko-navy); pointer-events: none;
    }
    .ko-grid-chip {
        display: inline-flex; align-items: center; gap: 0.35rem;
        background: #f3f2f1; border: 1px solid #e1dfdd; border-radius: 2px;
        padding: 0.28rem 0.5rem; font-size: 0.8rem; color: #242424;
    }
    .ko-grid-chip button { border: 0; background: transparent; padding: 0; line-height: 1; color: #605e5c; }
    .ko-grid-body { display: grid; flex: 1; min-height: 0; }
    .ko-grid.is-focused .ko-grid-body { grid-template-columns: minmax(0, 1fr) 22rem; }
    .ko-grid-table-wrap { overflow: auto; max-height: calc(100vh - 12rem); }
    .ko-grid-table { width: 100%; border-collapse: collapse; }
    .ko-grid-table thead th {
        position: sticky; top: 0; z-index: 1; background: #fff;
        font-size: 0.78rem; font-weight: 600; color: var(--ko-muted); text-transform: none; letter-spacing: 0;
        border-bottom: 1px solid var(--ko-line); padding: 0.55rem 0.65rem; white-space: nowrap;
        border-right: 1px solid #f3f2f1;
    }
    .ko-grid-table thead th:last-child { border-right: 0; }
    .ko-grid-table tbody td {
        padding: 0.62rem 0.65rem; border-bottom: 1px solid #f3f2f1; vertical-align: middle; color: #242424;
    }
    .ko-grid-table tbody tr:hover { background: #f3f2f1; }
    .ko-grid-table tbody tr.table-active { background: #e8ebf5; }
    .ko-grid-table tbody tr.filtered-out { display: none; }
    .ko-grid-check { width: 2.4rem; }
    .ko-grid-link { color: var(--ko-link); text-decoration: none; font-weight: 600; }
    .ko-grid-link:hover { color: var(--ko-red); text-decoration: underline; }
    .ko-grid-preview {
        display: none; border-left: 1px solid var(--ko-line); background: #fafafa; padding: 1rem 1.1rem;
    }
    .ko-grid.is-focused .ko-grid-preview { display: block; }
    .ko-grid-preview h2 { font-size: 1rem; font-weight: 650; margin: 0 0 0.75rem; color: var(--ko-navy); }
    .ko-grid-msg {
        background: #fff; border: 1px solid var(--ko-line); border-radius: 4px;
        padding: 0.85rem; white-space: pre-wrap; word-break: break-word; font-size: 0.88rem;
    }
    .ko-grid-empty { text-align: center; padding: 3.5rem 1rem; color: #605e5c; }
    .ko-grid-alert { margin: 0.75rem 1rem 0; }
    .ko-cats {
        display: flex; flex-wrap: wrap; align-items: stretch; gap: 0;
        padding: 0 0.5rem; border-bottom: 1px solid var(--ko-line); background: #fafafa;
    }
    .ko-cat {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.7rem 1rem; text-decoration: none; color: #242424;
        font-weight: 600; font-size: 0.88rem; border-bottom: 2px solid transparent; margin-bottom: -1px;
    }
    .ko-cat:hover { color: var(--ko-navy); background: #f3f2f1; }
    .ko-cat.is-on { color: var(--ko-navy); border-bottom-color: var(--ko-red); }
    .ko-cat em {
        font-style: normal; font-size: 0.72rem; font-weight: 700;
        background: #edebe9; color: #323130; border-radius: 999px; padding: 0.05rem 0.45rem;
    }
    .ko-cat.is-on em { background: #e8ebf5; color: var(--ko-navy); }
    .ko-cats-sub {
        gap: 0.4rem; padding: 0.5rem 1rem; background: #fff; border-bottom: 1px solid var(--ko-line);
    }
    .ko-cats-sub .ko-cat {
        border: 1px solid #e1dfdd; border-radius: 999px; padding: 0.28rem 0.75rem;
        margin: 0; font-weight: 600; font-size: 0.8rem; border-bottom: 1px solid #e1dfdd;
    }
    .ko-cats-sub .ko-cat.is-on {
        background: var(--ko-navy); color: #fff; border-color: var(--ko-navy);
    }
    .ko-cats-sub .ko-cat.is-on em { background: rgba(255,255,255,0.2); color: #fff; }
    @media (max-width: 991.98px) {
        .ko-grid.is-focused .ko-grid-body { grid-template-columns: 1fr; }
        .ko-grid.is-focused .ko-grid-preview { border-left: 0; border-top: 1px solid var(--ko-line); }
        .ko-grid { margin-left: -1rem; margin-right: -1rem; }
    }
</style>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/tools/partials/erp-messages-grid-styles.blade.php ENDPATH**/ ?>