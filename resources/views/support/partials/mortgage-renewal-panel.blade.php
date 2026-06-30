{{-- Client action panel for mortgage renewals --}}
<div class="offcanvas offcanvas-end mat-action-panel" tabindex="-1" id="mortgageRenewalPanel" aria-labelledby="mortgageRenewalPanelLabel">
    <div class="mat-panel-header">
        <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
                <p class="mat-panel-eyebrow mb-1">Mortgage renewal</p>
                <h5 class="fw-bold mb-1" id="mortgageRenewalPanelLabel">Client actions</h5>
                <div class="mat-panel-meta">
                    <span class="font-monospace fw-semibold" id="mr_panel_policy">—</span>
                    <span class="mx-2 opacity-50">·</span>
                    <span id="mr_panel_renewal">—</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
            <span id="mr_panel_countdown" class="mat-days-pill mat-days-pill--normal">—</span>
            <span class="mat-product-badge-light" id="mr_panel_product">—</span>
            <span id="mr_panel_status"></span>
        </div>
    </div>

    <div class="offcanvas-body mat-panel-body p-0">
        <div class="mat-panel-client card border-0 rounded-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="mat-panel-avatar" id="mr_panel_avatar">—</div>
                    <div class="min-w-0">
                        <div class="fw-semibold text-truncate" id="mr_panel_client">—</div>
                        <a href="#" class="small text-decoration-none" id="mr_panel_client_link" style="color:var(--agile-primary,#1B3F7A)" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open client record
                        </a>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="mat-contact-card">
                            <div class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i>Email</div>
                            <div class="fw-medium text-break" id="mr_panel_email">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mat-contact-card">
                            <div class="small text-muted mb-1"><i class="bi bi-phone me-1"></i>Phone</div>
                            <div class="fw-medium" id="mr_panel_phone">—</div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3" id="mr_panel_notify_badges"></div>
            </div>
        </div>

        <div class="mat-panel-actions p-4">
            <div class="mat-action-block mb-4">
                <div class="mat-action-block-head">
                    <span class="mat-action-icon" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-envelope-at"></i></span>
                    <div>
                        <h6 class="fw-bold mb-0">Renewal reminder email</h6>
                        <p class="small text-muted mb-0">Send a personalised renewal notice to the client.</p>
                    </div>
                </div>
                <form method="post" action="{{ route('support.maturities.notify-client') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <input type="hidden" name="screen" value="mortgage">
                    <input type="hidden" name="channel" value="email">
                    <input type="hidden" name="event_type" value="renewal">
                    <input type="hidden" name="policy_number" id="mr_email_policy" value="">
                    <input type="hidden" name="event_date" id="mr_email_date" value="">
                    <input type="hidden" name="client_name" id="mr_email_client" value="">
                    <input type="hidden" name="product" id="mr_email_product" value="">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Client email</label>
                            <input type="email" class="form-control form-control-sm" name="to_email" id="mr_email_to" required placeholder="client@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Subject</label>
                            <input type="text" class="form-control form-control-sm" name="subject" id="mr_email_subject" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Message</label>
                            <textarea class="form-control form-control-sm" name="message" id="mr_email_message" rows="4" maxlength="2000" placeholder="Leave blank to use the standard template."></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                                <i class="bi bi-send me-1"></i>Send renewal email
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="mat-action-block mb-4">
                <div class="mat-action-block-head">
                    <span class="mat-action-icon" style="background:var(--agile-primary-muted,rgba(27,63,122,0.08));color:var(--agile-primary,#1B3F7A)"><i class="bi bi-chat-dots"></i></span>
                    <div>
                        <h6 class="fw-bold mb-0">SMS reminder</h6>
                        <p class="small text-muted mb-0">Text the client about their upcoming mortgage renewal.</p>
                    </div>
                </div>
                <form method="post" action="{{ route('support.maturities.notify-client') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <input type="hidden" name="screen" value="mortgage">
                    <input type="hidden" name="channel" value="sms">
                    <input type="hidden" name="event_type" value="renewal">
                    <input type="hidden" name="policy_number" id="mr_sms_policy" value="">
                    <input type="hidden" name="event_date" id="mr_sms_date" value="">
                    <input type="hidden" name="client_name" id="mr_sms_client" value="">
                    <input type="hidden" name="product" id="mr_sms_product" value="">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Client phone</label>
                            <input type="text" class="form-control form-control-sm" name="to_phone" id="mr_sms_phone" required placeholder="07xx xxx xxx">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Message</label>
                            <textarea class="form-control form-control-sm" name="message" id="mr_sms_message" rows="3" maxlength="2000" placeholder="Leave blank to use the standard SMS template."></textarea>
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

            <div class="mat-action-block mb-0">
                <div class="mat-action-block-head">
                    <span class="mat-action-icon" style="background:#f1f5f9;color:#475569"><i class="bi bi-ticket-perforated"></i></span>
                    <div>
                        <h6 class="fw-bold mb-0">Support ticket</h6>
                        <p class="small text-muted mb-0">Create a renewal follow-up ticket for this policy.</p>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="#" class="btn btn-outline-primary btn-sm fw-semibold" id="mr_panel_ticket">
                        <i class="bi bi-ticket-perforated me-1"></i>Create ticket
                    </a>
                    <a href="#" class="btn btn-outline-secondary btn-sm" id="mr_panel_serve">
                        <i class="bi bi-person-plus me-1"></i>Serve client
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var panel = document.getElementById('mortgageRenewalPanel');
    if (!panel) return;

    var clientShowUrl = @json(route('support.clients.show'));
    var ticketUrl = @json(route('support.clients.create-ticket'));
    var serveUrl = @json(route('support.serve-client'));
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
        var el = document.getElementById('mr_panel_countdown');
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
        var wrap = document.getElementById('mr_panel_notify_badges');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (emailSent) {
            wrap.insertAdjacentHTML('beforeend', '<span class="mat-notify-badge mat-notify-badge--done"><i class="bi bi-envelope-check"></i> Email sent</span>');
        }
        if (smsSent) {
            wrap.insertAdjacentHTML('beforeend', '<span class="mat-notify-badge mat-notify-badge--done"><i class="bi bi-chat-dots-fill"></i> SMS sent</span>');
        }
        if (!emailSent && !smsSent) {
            wrap.insertAdjacentHTML('beforeend', '<span class="mat-notify-badge mat-notify-badge--pending"><i class="bi bi-hourglass-split"></i> Client not yet notified</span>');
        }
    }

    function fillPanel(data) {
        var policy = data.policy || '';
        var renewalYmd = data.renewalYmd || '';
        var renewalDisplay = data.renewalDisplay || renewalYmd;
        var clientName = data.clientName || '';
        var product = data.product || '';
        var email = data.email || '';
        var phone = data.phone || '';
        var subject = data.subject || '';
        var status = data.status || '';

        document.getElementById('mortgageRenewalPanelLabel').textContent = clientName || 'Client actions';
        setText('mr_panel_policy', policy);
        setText('mr_panel_renewal', renewalDisplay);
        setText('mr_panel_product', product);
        setText('mr_panel_client', clientName);
        setText('mr_panel_email', email);
        setText('mr_panel_phone', phone);
        document.getElementById('mr_panel_avatar').textContent = initials(clientName);
        countdownPill(data.daysToRenewal);
        notifyBadges(data.emailSent === '1', data.smsSent === '1');

        var statusEl = document.getElementById('mr_panel_status');
        if (statusEl) {
            statusEl.innerHTML = status
                ? '<span class="badge bg-light text-dark border">' + status + '</span>'
                : '';
        }

        var clientLink = document.getElementById('mr_panel_client_link');
        if (clientLink && policy) {
            clientLink.href = clientShowUrl + '?policy=' + encodeURIComponent(policy) + '&system=mortgage';
        }

        var ticketLink = document.getElementById('mr_panel_ticket');
        if (ticketLink && policy) {
            ticketLink.href = ticketUrl + '?policy=' + encodeURIComponent(policy) + '&system=mortgage';
        }

        var serveLink = document.getElementById('mr_panel_serve');
        if (serveLink && policy) {
            serveLink.href = serveUrl + '?search=' + encodeURIComponent(policy);
        }

        setInput('mr_email_policy', policy);
        setInput('mr_email_date', renewalYmd);
        setInput('mr_email_client', clientName);
        setInput('mr_email_product', product);
        setInput('mr_email_to', email);
        setInput('mr_email_subject', subject);

        setInput('mr_sms_policy', policy);
        setInput('mr_sms_date', renewalYmd);
        setInput('mr_sms_client', clientName);
        setInput('mr_sms_product', product);
        setInput('mr_sms_phone', phone);

        if (policy && (!email || !phone)) {
            fetch(contactUrl + '?policy=' + encodeURIComponent(policy), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (contact) {
                    if (!contact) return;
                    if (!email && contact.email) {
                        setText('mr_panel_email', contact.email);
                        setInput('mr_email_to', contact.email);
                    }
                    if (!phone && contact.phone) {
                        setText('mr_panel_phone', contact.phone);
                        setInput('mr_sms_phone', contact.phone);
                    }
                })
                .catch(function () {});
        }
    }

    panel.addEventListener('show.bs.offcanvas', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.classList.contains('mr-open-panel')) return;
        fillPanel({
            policy: btn.getAttribute('data-policy') || '',
            renewalYmd: btn.getAttribute('data-renewal-ymd') || '',
            renewalDisplay: btn.getAttribute('data-renewal-display') || '',
            clientName: btn.getAttribute('data-client-name') || '',
            product: btn.getAttribute('data-product') || '',
            email: btn.getAttribute('data-email') || '',
            phone: btn.getAttribute('data-phone') || '',
            subject: btn.getAttribute('data-subject') || '',
            status: btn.getAttribute('data-status') || '',
            daysToRenewal: btn.getAttribute('data-days'),
            emailSent: btn.getAttribute('data-email-sent') || '0',
            smsSent: btn.getAttribute('data-sms-sent') || '0',
        });
    });
})();
</script>
@endpush
