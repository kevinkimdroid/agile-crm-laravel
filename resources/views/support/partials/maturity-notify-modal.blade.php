{{-- Notify client modal — shared by maturities / investment / mortgage screens --}}
<div class="modal fade" id="maturityNotifyModal" tabindex="-1" aria-labelledby="maturityNotifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('support.maturities.notify-client') }}" id="maturityNotifyForm">
                @csrf
                <input type="hidden" name="return_url" id="mn_return_url" value="{{ request()->fullUrl() }}">
                <input type="hidden" name="screen" id="mn_screen" value="">
                <input type="hidden" name="channel" id="mn_channel" value="email">
                <input type="hidden" name="event_type" id="mn_event_type" value="maturity">
                <input type="hidden" name="policy_number" id="mn_policy" value="">
                <input type="hidden" name="event_date" id="mn_event_date" value="">
                <input type="hidden" name="client_name" id="mn_client_name" value="">
                <input type="hidden" name="product" id="mn_product" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="maturityNotifyModalLabel">Notify client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Policy <span class="font-monospace fw-semibold text-dark" id="mn_policy_label"></span>
                        · <span id="mn_event_label">maturity</span> <span id="mn_date_label"></span>
                    </p>
                    <div class="mb-3" id="mn_email_group">
                        <label for="mn_to_email" class="form-label small mb-0">Client email</label>
                        <input type="email" class="form-control form-control-sm" name="to_email" id="mn_to_email" placeholder="client@example.com">
                    </div>
                    <div class="mb-3 d-none" id="mn_phone_group">
                        <label for="mn_to_phone" class="form-label small mb-0">Client phone</label>
                        <input type="text" class="form-control form-control-sm" name="to_phone" id="mn_to_phone" placeholder="07xx xxx xxx">
                    </div>
                    <div class="mb-3" id="mn_subject_group">
                        <label for="mn_subject" class="form-label small mb-0">Subject</label>
                        <input type="text" class="form-control form-control-sm" name="subject" id="mn_subject" maxlength="255">
                    </div>
                    <div class="mb-0">
                        <label for="mn_message" class="form-label small mb-0">Message</label>
                        <textarea class="form-control form-control-sm" name="message" id="mn_message" rows="5" maxlength="2000"></textarea>
                        <div class="form-text">Leave blank to use the standard template.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="mn_submit_btn"><i class="bi bi-send me-1"></i> Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('maturityNotifyModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        var channel = btn.getAttribute('data-channel') || 'email';
        var screen = btn.getAttribute('data-screen') || 'maturities';
        var eventType = btn.getAttribute('data-event-type') || 'maturity';
        var policy = btn.getAttribute('data-policy') || '';
        var eventDate = btn.getAttribute('data-event-date') || '';
        var clientName = btn.getAttribute('data-client-name') || '';
        var product = btn.getAttribute('data-product') || '';
        var email = btn.getAttribute('data-email') || '';
        var phone = btn.getAttribute('data-phone') || '';
        var subject = btn.getAttribute('data-subject') || '';
        var message = btn.getAttribute('data-message') || '';

        document.getElementById('mn_channel').value = channel;
        document.getElementById('mn_screen').value = screen;
        document.getElementById('mn_event_type').value = eventType;
        document.getElementById('mn_policy').value = policy;
        document.getElementById('mn_event_date').value = eventDate;
        document.getElementById('mn_client_name').value = clientName;
        document.getElementById('mn_product').value = product;
        document.getElementById('mn_policy_label').textContent = policy;
        document.getElementById('mn_date_label').textContent = eventDate;
        document.getElementById('mn_event_label').textContent = eventType === 'renewal' ? 'renewal' : 'maturity';
        document.getElementById('mn_to_email').value = email;
        document.getElementById('mn_to_phone').value = phone;
        document.getElementById('mn_subject').value = subject;
        document.getElementById('mn_message').value = message;

        var isEmail = channel === 'email';
        document.getElementById('mn_email_group').classList.toggle('d-none', !isEmail);
        document.getElementById('mn_phone_group').classList.toggle('d-none', isEmail);
        document.getElementById('mn_subject_group').classList.toggle('d-none', !isEmail);
        document.getElementById('mn_to_email').required = isEmail;
        document.getElementById('mn_to_phone').required = !isEmail;
        document.getElementById('maturityNotifyModalLabel').textContent = isEmail ? 'Email client' : 'SMS client';
        document.getElementById('mn_submit_btn').innerHTML = isEmail
            ? '<i class="bi bi-envelope-at me-1"></i> Send email'
            : '<i class="bi bi-chat-dots me-1"></i> Send SMS';

        var needsEmail = isEmail && !email;
        var needsPhone = !isEmail && !phone;
        if (policy && (needsEmail || needsPhone)) {
            var phoneEl = document.getElementById('mn_to_phone');
            var emailEl = document.getElementById('mn_to_email');
            if (needsPhone && phoneEl) {
                phoneEl.placeholder = 'Loading from client details…';
            }
            if (needsEmail && emailEl) {
                emailEl.placeholder = 'Loading from client details…';
            }
            fetch(@json(route('support.maturities.client-contact')) + '?policy=' + encodeURIComponent(policy), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    if (needsEmail && data.email && emailEl) {
                        emailEl.value = data.email;
                        emailEl.placeholder = 'client@example.com';
                    }
                    if (needsPhone && data.phone && phoneEl) {
                        phoneEl.value = data.phone;
                        phoneEl.placeholder = '07xx xxx xxx';
                    }
                })
                .catch(function () {})
                .finally(function () {
                    if (phoneEl && !phoneEl.value) phoneEl.placeholder = '07xx xxx xxx';
                    if (emailEl && !emailEl.value) emailEl.placeholder = 'client@example.com';
                });
        }
    });
})();
</script>
@endpush
