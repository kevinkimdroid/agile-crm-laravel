{{-- Full-width client action panel for investment maturities --}}
<div class="offcanvas offcanvas-end inv-action-panel" tabindex="-1" id="invMaturityPanel" aria-labelledby="invMaturityPanelLabel">
    <div class="inv-panel-header">
        <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
                <p class="inv-panel-eyebrow mb-1">Investment maturity</p>
                <h5 class="fw-bold mb-1" id="invMaturityPanelLabel">Client actions</h5>
                <div class="inv-panel-meta">
                    <span class="font-monospace fw-semibold" id="inv_panel_policy">—</span>
                    <span class="mx-2 opacity-50">·</span>
                    <span id="inv_panel_maturity">—</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
            <span id="inv_panel_countdown" class="mat-days-pill mat-days-pill--normal">—</span>
            <span class="inv-product-badge" id="inv_panel_product">—</span>
        </div>
    </div>

    <div class="offcanvas-body inv-panel-body p-0">
        <div class="inv-panel-client card border-0 rounded-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="inv-panel-avatar" id="inv_panel_avatar">—</div>
                    <div class="min-w-0">
                        <div class="fw-semibold text-truncate" id="inv_panel_client">—</div>
                        <a href="#" class="small text-decoration-none" id="inv_panel_client_link" style="color:var(--agile-primary,#1B3F7A)" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open client record
                        </a>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="inv-contact-card">
                            <div class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i>Email</div>
                            <div class="fw-medium text-break" id="inv_panel_email">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="inv-contact-card">
                            <div class="small text-muted mb-1"><i class="bi bi-phone me-1"></i>Phone</div>
                            <div class="fw-medium" id="inv_panel_phone">—</div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3" id="inv_panel_notify_badges"></div>
            </div>
        </div>

        <div class="inv-panel-actions p-4">
            <div class="inv-action-block mb-4">
                <div class="inv-action-block-head">
                    <span class="inv-action-icon" style="background:#fee2e2;color:#b91c1c"><i class="bi bi-file-pdf"></i></span>
                    <div>
                        <h6 class="fw-bold mb-0">Discharge voucher</h6>
                        <p class="small text-muted mb-0">Download PDF or email the voucher to the client.</p>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3 mb-3">
                    <a href="#" class="btn btn-outline-danger btn-sm fw-semibold" id="inv_panel_dv_pdf" target="_blank" rel="noopener">
                        <i class="bi bi-download me-1"></i>Download PDF
                    </a>
                </div>
                <form method="post" action="{{ route('support.maturities.discharge-voucher.email') }}" class="inv-action-form">
                    @csrf
                    <input type="hidden" name="policy_number" id="inv_dv_policy" value="">
                    <input type="hidden" name="maturity" id="inv_dv_maturity" value="">
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Recipient email</label>
                            <input type="email" class="form-control form-control-sm" name="to_email" id="inv_dv_email" required placeholder="client@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Recipient name</label>
                            <input type="text" class="form-control form-control-sm" name="to_name" id="inv_dv_name" placeholder="Client name">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Cover message (optional)</label>
                            <textarea class="form-control form-control-sm" name="message" id="inv_dv_message" rows="3" maxlength="5000" placeholder="Leave blank for the standard discharge voucher message."></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger btn-sm fw-semibold">
                                <i class="bi bi-envelope-paper me-1"></i>Email discharge voucher
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="inv-action-block mb-4">
                <div class="inv-action-block-head">
                    <span class="inv-action-icon" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-envelope-at"></i></span>
                    <div>
                        <h6 class="fw-bold mb-0">Maturity reminder email</h6>
                        <p class="small text-muted mb-0">Send a personalised maturity notice to the client.</p>
                    </div>
                </div>
                <form method="post" action="{{ route('support.maturities.notify-client') }}" class="inv-action-form mt-3">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <input type="hidden" name="screen" value="investment">
                    <input type="hidden" name="channel" value="email">
                    <input type="hidden" name="event_type" value="maturity">
                    <input type="hidden" name="policy_number" id="inv_email_policy" value="">
                    <input type="hidden" name="event_date" id="inv_email_date" value="">
                    <input type="hidden" name="client_name" id="inv_email_client" value="">
                    <input type="hidden" name="product" id="inv_email_product" value="">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Client email</label>
                            <input type="email" class="form-control form-control-sm" name="to_email" id="inv_email_to" required placeholder="client@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Subject</label>
                            <input type="text" class="form-control form-control-sm" name="subject" id="inv_email_subject" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Message</label>
                            <textarea class="form-control form-control-sm" name="message" id="inv_email_message" rows="4" maxlength="2000" placeholder="Leave blank to use the standard template."></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                                <i class="bi bi-send me-1"></i>Send maturity email
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="inv-action-block mb-2">
                <div class="inv-action-block-head">
                    <span class="inv-action-icon" style="background:var(--agile-primary-muted,rgba(27,63,122,0.08));color:var(--agile-primary,#1B3F7A)"><i class="bi bi-chat-dots"></i></span>
                    <div>
                        <h6 class="fw-bold mb-0">SMS reminder</h6>
                        <p class="small text-muted mb-0">Text the client about their upcoming investment maturity.</p>
                    </div>
                </div>
                <form method="post" action="{{ route('support.maturities.notify-client') }}" class="inv-action-form mt-3">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <input type="hidden" name="screen" value="investment">
                    <input type="hidden" name="channel" value="sms">
                    <input type="hidden" name="event_type" value="maturity">
                    <input type="hidden" name="policy_number" id="inv_sms_policy" value="">
                    <input type="hidden" name="event_date" id="inv_sms_date" value="">
                    <input type="hidden" name="client_name" id="inv_sms_client" value="">
                    <input type="hidden" name="product" id="inv_sms_product" value="">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Client phone</label>
                            <input type="text" class="form-control form-control-sm" name="to_phone" id="inv_sms_phone" required placeholder="07xx xxx xxx">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Message</label>
                            <textarea class="form-control form-control-sm" name="message" id="inv_sms_message" rows="3" maxlength="2000" placeholder="Leave blank to use the standard SMS template."></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm fw-semibold" @if(!($smsConfigured ?? false)) disabled title="SMS not configured" @endif>
                                <i class="bi bi-chat-dots me-1"></i>Send SMS
                            </button>
                            @if(!($smsConfigured ?? false))
                                <span class="small text-muted ms-2">Configure Advanta SMS in .env</span>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var panel = document.getElementById('invMaturityPanel');
    if (!panel) return;

    var pdfBase = @json(route('support.maturities.discharge-voucher.pdf'));
    var clientShowUrl = @json(route('support.clients.show'));
    var contactUrl = @json(route('support.maturities.client-contact'));

    function setText(id, value, fallback) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || fallback || '—';
    }

    function setInput(id, value) {
        var el = document.getElementById(id);
        if (el) el.value = value || '';
    }

    function initials(name) {
        var parts = (name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '?';
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    function countdownPill(days) {
        var el = document.getElementById('inv_panel_countdown');
        if (!el) return;
        el.className = 'mat-days-pill';
        if (days === null || days === undefined || days === '') {
            el.classList.add('mat-days-pill--normal');
            el.textContent = '—';
            return;
        }
        days = parseInt(days, 10);
        if (days < 0) {
            el.classList.add('mat-days-pill--overdue');
            el.innerHTML = '<i class="bi bi-exclamation-circle"></i> Overdue ' + Math.abs(days) + 'd';
        } else if (days === 0) {
            el.classList.add('mat-days-pill--today');
            el.innerHTML = '<i class="bi bi-alarm"></i> Today';
        } else if (days <= 7) {
            el.classList.add('mat-days-pill--soon');
            el.innerHTML = '<i class="bi bi-clock"></i> ' + days + ' days';
        } else {
            el.classList.add('mat-days-pill--normal');
            el.textContent = days + ' days';
        }
    }

    function notifyBadges(emailSent, smsSent) {
        var wrap = document.getElementById('inv_panel_notify_badges');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (emailSent) {
            wrap.insertAdjacentHTML('beforeend', '<span class="inv-notify-badge inv-notify-badge--done"><i class="bi bi-envelope-check"></i> Email sent</span>');
        }
        if (smsSent) {
            wrap.insertAdjacentHTML('beforeend', '<span class="inv-notify-badge inv-notify-badge--done"><i class="bi bi-chat-dots-fill"></i> SMS sent</span>');
        }
        if (!emailSent && !smsSent) {
            wrap.insertAdjacentHTML('beforeend', '<span class="inv-notify-badge inv-notify-badge--pending"><i class="bi bi-hourglass-split"></i> Client not yet notified</span>');
        }
    }

    function fillPanel(data) {
        var policy = data.policy || '';
        var maturityYmd = data.maturityYmd || '';
        var maturityDisplay = data.maturityDisplay || maturityYmd;
        var clientName = data.clientName || '';
        var product = data.product || '';
        var email = data.email || '';
        var phone = data.phone || '';
        var subject = data.subject || '';
        var days = data.daysToMaturity;

        document.getElementById('invMaturityPanelLabel').textContent = clientName || 'Client actions';
        setText('inv_panel_policy', policy);
        setText('inv_panel_maturity', maturityDisplay);
        setText('inv_panel_product', product);
        setText('inv_panel_client', clientName);
        setText('inv_panel_email', email);
        setText('inv_panel_phone', phone);
        document.getElementById('inv_panel_avatar').textContent = initials(clientName);
        countdownPill(days);
        notifyBadges(data.emailSent === '1', data.smsSent === '1');

        var clientLink = document.getElementById('inv_panel_client_link');
        if (clientLink && policy) {
            clientLink.href = clientShowUrl + '?policy=' + encodeURIComponent(policy);
        }

        var pdfLink = document.getElementById('inv_panel_dv_pdf');
        if (pdfLink && policy && maturityYmd) {
            pdfLink.href = pdfBase + '?policy_number=' + encodeURIComponent(policy) + '&maturity=' + encodeURIComponent(maturityYmd);
        }

        setInput('inv_dv_policy', policy);
        setInput('inv_dv_maturity', maturityYmd);
        setInput('inv_dv_email', email);
        setInput('inv_dv_name', clientName);

        setInput('inv_email_policy', policy);
        setInput('inv_email_date', maturityYmd);
        setInput('inv_email_client', clientName);
        setInput('inv_email_product', product);
        setInput('inv_email_to', email);
        setInput('inv_email_subject', subject);

        setInput('inv_sms_policy', policy);
        setInput('inv_sms_date', maturityYmd);
        setInput('inv_sms_client', clientName);
        setInput('inv_sms_product', product);
        setInput('inv_sms_phone', phone);

        if (policy && (!email || !phone)) {
            fetch(contactUrl + '?policy=' + encodeURIComponent(policy), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (contact) {
                    if (!contact) return;
                    if (!email && contact.email) {
                        email = contact.email;
                        setText('inv_panel_email', email);
                        setInput('inv_dv_email', email);
                        setInput('inv_email_to', email);
                    }
                    if (!phone && contact.phone) {
                        phone = contact.phone;
                        setText('inv_panel_phone', phone);
                        setInput('inv_sms_phone', phone);
                    }
                })
                .catch(function () {});
        }
    }

    panel.addEventListener('show.bs.offcanvas', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.classList.contains('inv-open-panel')) return;
        fillPanel({
            policy: btn.getAttribute('data-policy') || '',
            maturityYmd: btn.getAttribute('data-maturity-ymd') || '',
            maturityDisplay: btn.getAttribute('data-maturity-display') || '',
            clientName: btn.getAttribute('data-client-name') || '',
            product: btn.getAttribute('data-product') || '',
            email: btn.getAttribute('data-email') || '',
            phone: btn.getAttribute('data-phone') || '',
            subject: btn.getAttribute('data-subject') || '',
            daysToMaturity: btn.getAttribute('data-days'),
            emailSent: btn.getAttribute('data-email-sent') || '0',
            smsSent: btn.getAttribute('data-sms-sent') || '0',
        });
    });
})();
</script>
@endpush
