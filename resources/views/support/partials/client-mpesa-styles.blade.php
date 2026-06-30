<style>
/* ── M-Pesa payment UI (client screen) ── */
:root {
    --mpesa-green: #30B54A;
    --mpesa-green-dark: #1a7f37;
    --mpesa-green-soft: #ecfdf3;
    --mpesa-shadow: 0 8px 32px rgba(26, 127, 55, 0.12);
    --mpesa-radius: 16px;
}
.mpesa-ui {
    /* inherit from :root */
}

/* Header action — must use literal colors (outside .mpesa-ui wrapper) */
.client-mpesa-header-btn {
    background: linear-gradient(135deg, #1a7f37, #30B54A) !important;
    border: 1px solid #1a7f37 !important;
    color: #fff !important;
    box-shadow: 0 4px 14px rgba(48, 181, 74, 0.4);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.client-mpesa-header-btn:hover,
.client-mpesa-header-btn:focus {
    background: linear-gradient(135deg, #14532d, #1a7f37) !important;
    border-color: #14532d !important;
    color: #fff !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(48, 181, 74, 0.45);
}
.client-mpesa-header-btn i { color: #fff !important; }

/* Strip banner */
.client-mpesa-strip {
    position: relative;
    overflow: hidden;
    border-radius: var(--mpesa-radius);
    padding: 0;
    border: none;
    background: linear-gradient(120deg, #0f5132 0%, #1a7f37 45%, #30B54A 100%);
    box-shadow: var(--mpesa-shadow);
}
.client-mpesa-strip::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 85% 20%, rgba(255,255,255,0.15) 0%, transparent 45%);
    pointer-events: none;
}
.client-mpesa-strip-inner {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    padding: 1.15rem 1.35rem;
}
.client-mpesa-strip-text {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #fff;
}
.client-mpesa-strip-icon {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 14px;
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.client-mpesa-strip-text strong {
    display: block;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.01em;
}
.client-mpesa-strip-text span {
    display: block;
    font-size: 0.82rem;
    opacity: 0.88;
    margin-top: 0.15rem;
}
.client-mpesa-strip .mpesa-btn-primary {
    background: #fff;
    color: var(--mpesa-green-dark);
    border: none;
    font-weight: 700;
    padding: 0.55rem 1.25rem;
    border-radius: 999px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.client-mpesa-strip .mpesa-btn-primary:hover {
    background: #f0fdf4;
    color: var(--mpesa-green-dark);
    transform: translateY(-1px);
}

/* Premium card */
.client-mpesa-pay-card {
    border: none;
    border-radius: var(--mpesa-radius);
    overflow: hidden;
    box-shadow: var(--mpesa-shadow);
    background: #fff;
}
.client-mpesa-pay-card .card-body { padding: 0; }
.mpesa-card-hero {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1.5rem;
    padding: 1.5rem 1.5rem 1.25rem;
    background: linear-gradient(145deg, #f0fdf4 0%, #fff 55%);
    border-bottom: 1px solid rgba(48, 181, 74, 0.12);
}
@media (max-width: 767px) {
    .mpesa-card-hero { grid-template-columns: 1fr; }
    .mpesa-phone-preview { max-width: 200px; margin: 0 auto; }
}
.mpesa-card-head {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.mpesa-card-logo {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 14px;
    background: linear-gradient(145deg, var(--mpesa-green-dark), var(--mpesa-green));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
    box-shadow: 0 6px 16px rgba(48, 181, 74, 0.35);
}
.mpesa-card-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #14532d;
    letter-spacing: -0.02em;
    margin: 0 0 0.35rem;
}
.mpesa-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 0.5rem;
}
.mpesa-tag {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: rgba(48, 181, 74, 0.12);
    color: var(--mpesa-green-dark);
}
.mpesa-tag-sandbox { background: #e0f2fe; color: #0369a1; }
.mpesa-card-desc {
    font-size: 0.88rem;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
    max-width: 36rem;
}
.mpesa-card-desc .policy-ref {
    font-family: ui-monospace, monospace;
    font-weight: 600;
    color: #334155;
}

/* Phone preview mockup */
.mpesa-phone-preview {
    width: 168px;
    flex-shrink: 0;
}
.mpesa-phone-frame {
    background: #1e293b;
    border-radius: 22px;
    padding: 8px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.25);
}
.mpesa-phone-screen {
    background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
    border-radius: 16px;
    padding: 0.85rem 0.75rem;
    min-height: 140px;
    font-size: 0.68rem;
}
.mpesa-phone-notch {
    width: 40%;
    height: 4px;
    background: #cbd5e1;
    border-radius: 999px;
    margin: 0 auto 0.65rem;
}
.mpesa-phone-prompt {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.55rem 0.6rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.mpesa-phone-prompt-label {
    color: var(--mpesa-green);
    font-weight: 800;
    font-size: 0.62rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.mpesa-phone-prompt-amount {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0.15rem 0;
}
.mpesa-phone-prompt-hint {
    color: #64748b;
    font-size: 0.6rem;
}

/* Alerts inside card */
.mpesa-notice {
    display: flex;
    gap: 0.65rem;
    padding: 0.75rem 1rem;
    margin: 0 1.5rem 1rem;
    border-radius: 12px;
    font-size: 0.82rem;
    line-height: 1.45;
}
.mpesa-notice i { font-size: 1.1rem; flex-shrink: 0; margin-top: 0.1rem; }
.mpesa-notice-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.mpesa-notice-warn { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

/* Amount chips */
.mpesa-card-actions {
    padding: 0 1.5rem 1.5rem;
}
.mpesa-amt-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
    margin-bottom: 0.65rem;
}
.mpesa-amt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.mpesa-amt-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.65rem 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
    color: inherit;
}
.mpesa-amt-chip:hover {
    border-color: var(--mpesa-green);
    background: var(--mpesa-green-soft);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(48, 181, 74, 0.15);
}
.mpesa-amt-chip-suggested {
    border-color: rgba(48, 181, 74, 0.4);
    background: var(--mpesa-green-soft);
}
.mpesa-amt-chip-currency {
    font-size: 0.65rem;
    color: #64748b;
    font-weight: 600;
}
.mpesa-amt-chip-value {
    font-size: 1rem;
    font-weight: 800;
    color: #14532d;
}
.mpesa-btn-collect {
    width: 100%;
    padding: 0.75rem 1.25rem;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--mpesa-green-dark), var(--mpesa-green));
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
    box-shadow: 0 4px 16px rgba(48, 181, 74, 0.35);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.mpesa-btn-collect:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(48, 181, 74, 0.4); }

/* Recent payments */
.mpesa-tx-list { padding: 0 1.5rem 1.5rem; }
.mpesa-tx-list-head {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
    margin-bottom: 0.75rem;
}
.mpesa-tx-item {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.mpesa-tx-item:last-child { border-bottom: none; }
.mpesa-tx-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.mpesa-tx-icon.success { background: #dcfce7; color: #15803d; }
.mpesa-tx-icon.pending { background: #fef9c3; color: #a16207; }
.mpesa-tx-icon.failed { background: #fee2e2; color: #b91c1c; }
.mpesa-tx-icon.cancelled { background: #f1f5f9; color: #64748b; }
.mpesa-tx-body { flex: 1; min-width: 0; }
.mpesa-tx-amount { font-weight: 700; color: #0f172a; font-size: 0.9rem; }
.mpesa-tx-meta { font-size: 0.75rem; color: #64748b; }
.mpesa-tx-receipt {
    font-family: ui-monospace, monospace;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--mpesa-green-dark);
    background: var(--mpesa-green-soft);
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
}

#mpesaModalPhonePreview {
    margin: 0 auto 0.25rem;
    display: flex;
    justify-content: center;
}
#mpesaStkModal .modal-content {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.2);
}
.mpesa-modal-hero {
    position: relative;
    padding: 1.35rem 1.35rem 1rem;
    background: linear-gradient(135deg, #0f5132 0%, #1a7f37 50%, #30B54A 100%);
    color: #fff;
}
.mpesa-modal-hero::after {
    content: '';
    position: absolute;
    top: -30%;
    right: -20%;
    width: 60%;
    height: 120%;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
    pointer-events: none;
}
.mpesa-modal-hero .btn-close { filter: brightness(0) invert(1); opacity: 0.85; }
.mpesa-modal-title {
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin: 0 0 0.25rem;
}
.mpesa-modal-sub {
    font-size: 0.82rem;
    opacity: 0.9;
    margin: 0;
}
.mpesa-modal-body { padding: 1.25rem 1.35rem 1rem; }
.mpesa-field-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 0.4rem;
}
.mpesa-field-input {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.65rem 0.85rem;
    font-size: 1rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.mpesa-field-input:focus {
    border-color: var(--mpesa-green);
    box-shadow: 0 0 0 3px rgba(48, 181, 74, 0.15);
}
.mpesa-policy-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.85rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 1.15rem;
}
.mpesa-policy-pill span:first-child {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #94a3b8;
}
.mpesa-policy-pill span:last-child {
    font-family: ui-monospace, monospace;
    font-weight: 700;
    color: #0f172a;
}
.mpesa-modal-quick {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.5rem;
}
.mpesa-modal-quick .mpesa-quick-amt {
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 999px;
    padding: 0.25rem 0.65rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.12s ease;
}
.mpesa-modal-quick .mpesa-quick-amt:hover,
.mpesa-modal-quick .mpesa-quick-amt.is-active {
    border-color: var(--mpesa-green);
    background: var(--mpesa-green-soft);
    color: var(--mpesa-green-dark);
}
.mpesa-modal-footer {
    padding: 1rem 1.35rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.65rem;
    justify-content: flex-end;
}
.mpesa-modal-footer .btn-cancel {
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    color: #64748b;
    border: 1px solid #e2e8f0;
    background: #fff;
}
.mpesa-modal-footer .btn-send {
    border-radius: 10px;
    padding: 0.55rem 1.35rem;
    font-weight: 700;
    border: none;
    background: linear-gradient(135deg, var(--mpesa-green-dark), var(--mpesa-green));
    color: #fff;
    box-shadow: 0 4px 14px rgba(48, 181, 74, 0.35);
}
.mpesa-modal-footer .btn-send:disabled {
    opacity: 0.55;
    box-shadow: none;
}

/* Status card (modal + polling) */
.client-mpesa-status-card {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 1rem;
    border-radius: 14px;
    margin-top: 1rem;
    border: 1px solid transparent;
}
.client-mpesa-status-card.is-pending {
    background: linear-gradient(135deg, #eff6ff, #f0f9ff);
    border-color: #93c5fd;
}
.client-mpesa-status-card.is-success {
    background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
    border-color: #86efac;
}
.client-mpesa-status-card.is-warning {
    background: linear-gradient(135deg, #fffbeb, #fefce8);
    border-color: #fcd34d;
}
.client-mpesa-status-card.is-danger {
    background: linear-gradient(135deg, #fef2f2, #fff1f2);
    border-color: #fca5a5;
}
.client-mpesa-status-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 12px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    background: rgba(255,255,255,0.85);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.client-mpesa-status-card.is-pending .client-mpesa-status-icon { color: #2563eb; }
.client-mpesa-status-card.is-success .client-mpesa-status-icon { color: #16a34a; }
.client-mpesa-status-card.is-warning .client-mpesa-status-icon { color: #d97706; }
.client-mpesa-status-card.is-danger .client-mpesa-status-icon { color: #dc2626; }
.client-mpesa-status-text strong {
    display: block;
    font-size: 0.92rem;
    font-weight: 700;
    color: #0f172a;
}
.client-mpesa-status-text span {
    display: block;
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.15rem;
    line-height: 1.4;
}
.client-mpesa-receipt-pill {
    display: inline-block;
    margin-top: 0.4rem;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    background: rgba(255,255,255,0.9);
    font-family: ui-monospace, monospace;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--mpesa-green-dark);
}

/* Toasts */
.client-toast-stack {
    position: fixed;
    top: 1.25rem;
    right: 1.25rem;
    z-index: 1080;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-width: min(400px, calc(100vw - 2rem));
    pointer-events: none;
}
@media (max-width: 576px) {
    .client-toast-stack {
        top: auto;
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
        max-width: none;
    }
}
.client-toast {
    pointer-events: auto;
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 1rem 1.1rem;
    border-radius: 16px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.8);
    box-shadow: 0 16px 48px rgba(15, 23, 42, 0.18);
    transform: translateY(-12px);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.35s ease;
}
.client-toast.show { transform: translateY(0); opacity: 1; }
.client-toast-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 12px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.client-toast-success .client-toast-icon { background: #dcfce7; color: #15803d; }
.client-toast-error .client-toast-icon { background: #fee2e2; color: #b91c1c; }
.client-toast-body { flex: 1; min-width: 0; }
.client-toast-body strong {
    display: block;
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.2rem;
}
.client-toast-body p {
    margin: 0;
    font-size: 0.82rem;
    color: #64748b;
    line-height: 1.45;
}
.client-toast-close {
    border: 0;
    background: #f1f5f9;
    color: #64748b;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 8px;
    font-size: 1.1rem;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.client-toast-close:hover { background: #e2e8f0; color: #334155; }
.client-toast-mpesa {
    border-left: 4px solid var(--mpesa-green);
    background: linear-gradient(135deg, rgba(240,253,244,0.98) 0%, rgba(255,255,255,0.98) 100%);
}
.client-toast-mpesa-logo {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 12px;
    background: linear-gradient(145deg, var(--mpesa-green-dark), var(--mpesa-green));
    color: #fff;
    font-weight: 900;
    font-size: 1.15rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(48, 181, 74, 0.35);
}
.client-toast-mpesa-progress {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.6rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: rgba(48, 181, 74, 0.12);
    color: var(--mpesa-green-dark);
    font-size: 0.75rem;
    font-weight: 700;
}
.client-toast-mpesa-pulse {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: var(--mpesa-green);
    animation: mpesaPulse 1.2s ease-in-out infinite;
}
.client-toast-mpesa-done { border-left-color: #15803d; }
.client-toast-mpesa-failed { border-left-color: #dc2626; }
@keyframes mpesaPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.4); opacity: 0.5; }
}

/* Summary tab tx table refresh */
.mpesa-summary-card {
    border: none;
    border-radius: var(--mpesa-radius);
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}
.mpesa-summary-card .card-body { padding: 1.25rem 1.35rem; }
</style>
