@extends('layouts.app')

@section('title', 'Creating New Ticket')


@section('content')
@php
    $returnPolicy = $returnToPolicy ?? ($presetPolicy ?? null);
    $fromClientLanding = ($fromClient ?? false) || filled($returnPolicy);
@endphp
<nav class="mb-3">
    @if($fromClientLanding && $returnPolicy)
    <a href="{{ route('support.clients.show', ['policy' => $returnPolicy]) }}" class="text-muted small text-decoration-none">Client</a>
    <span class="text-muted mx-2">/</span>
    @elseif($fromServeClient ?? false)
    <a href="{{ route('support.customers') }}" class="text-muted small text-decoration-none">Clients</a>
    <span class="text-muted mx-2">/</span>
    @elseif($fromMailManager ?? false)
    <a href="{{ route('tools.mail-manager') }}" class="text-muted small text-decoration-none">Mail Manager</a>
    <span class="text-muted mx-2">/</span>
    @elseif($fromLead ?? false)
    <a href="{{ route('leads.show', $returnToLead ?? '') }}" class="text-muted small text-decoration-none">Lead</a>
    <span class="text-muted mx-2">/</span>
    @endif
    <a href="{{ route('tickets.index') }}" class="text-muted small text-decoration-none">Tickets</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">New Ticket</span>
</nav>
<h1 class="app-page-title mb-4">Create Ticket</h1>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('info'))
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<form method="POST" action="{{ route('tickets.store') }}">
    @csrf
    @if($returnPolicy)
    <input type="hidden" name="return_to_policy" value="{{ $returnPolicy }}">
    @elseif($fromServeClient ?? false)
    <input type="hidden" name="return_to_clients" value="1">
    @elseif($fromMailManager ?? false)
    <input type="hidden" name="return_to_mail_manager" value="1">
    <input type="hidden" name="email_id" value="{{ $emailId ?? '' }}">
    @elseif(($fromLead ?? false) && ($returnToLead ?? null))
    <input type="hidden" name="return_to_lead" value="{{ $returnToLead }}">
    @elseif($presetContactId ?? null)
    <input type="hidden" name="return_to_contact" value="{{ $presetContactId }}">
    @endif

    {{-- Quick essentials --}}
    <div class="app-card mb-4">
        <div class="p-4">
            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label fw-semibold">Ticket Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. Policy inquiry, claim follow-up" value="{{ old('title', $presetTitle ?? '') }}" required autofocus>
                    @error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Assigned To <span class="text-danger">*</span></label>
                    <select name="assigned_to" class="form-select" required>
                        @foreach ($users ?? [] as $u)
                        <option value="{{ $u->id }}" {{ old('assigned_to', auth()->guard('vtiger')->id()) == $u->id ? 'selected' : '' }}>
                            {{ trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: $u->user_name }}
                        </option>
                        @endforeach
                        @if(empty($users) || ($users ?? collect())->isEmpty())
                        <option value="{{ auth()->guard('vtiger')->id() ?? 1 }}" selected>Current user</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-6 position-relative" id="contactSearchWrapper">
                    <label class="form-label fw-semibold">Prospect / Client <span class="text-danger">*</span></label>
                    <div class="input-group contact-select-wrapper">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-person-fill text-muted"></i></span>
                        <input type="text" id="contactSearch" class="form-control contact-select-input" placeholder="Type name or policy number to search" autocomplete="off" value="{{ old('contact_display', $presetContactDisplay ?? '') }}" {{ ($presetContactId ?? null) && (($fromServeClient ?? false) || $fromClientLanding) ? 'readonly' : '' }} aria-autocomplete="list" aria-expanded="false" aria-controls="contactDropdown" role="combobox">
                        <input type="hidden" name="contact_id" id="contactId" value="{{ old('contact_id', $presetContactId ?? '') }}" required>
                        @if(($presetContactId ?? null) && (($fromServeClient ?? false) || $fromClientLanding))
                        <a href="{{ $returnPolicy ? route('support.clients.show', ['policy' => $returnPolicy]) : route('support.customers') }}" class="btn btn-outline-secondary" title="Change client">Change</a>
                        @else
                        <button type="button" id="contactBrowse" class="btn btn-outline-primary" title="Browse all clients"><i class="bi bi-list-ul me-1"></i>Browse</button>
                        <button type="button" id="contactChange" class="btn btn-outline-secondary d-none" title="Choose different client"><i class="bi bi-arrow-repeat me-1"></i>Change</button>
                        <a href="{{ route('contacts.create') }}" class="btn btn-outline-secondary" title="Add new prospect"><i class="bi bi-plus-lg"></i></a>
                        @endif
                    </div>
                    <small class="text-muted d-block mt-1" id="contactSearchHint">Type to search or click Browse to select a client</small>
                    <div id="contactDropdown" class="contact-select-dropdown list-group position-absolute w-100 mt-1 shadow rounded-3" role="listbox"></div>
                    @error('contact_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Policy Number</label>
                    @if(!empty($policyOptions) && count($policyOptions) > 1)
                    <select name="policy_number" id="policy_number" class="form-select font-monospace">
                        @foreach($policyOptions as $opt)
                        @php $optVal = is_array($opt) ? ($opt['policy_no'] ?? $opt['policy_number'] ?? '') : (string) $opt; @endphp
                        @if($optVal !== '')
                        <option value="{{ $optVal }}" {{ old('policy_number', $presetPolicy ?? '') == $optVal ? 'selected' : '' }}>{{ $optVal }}@if(is_array($opt) && !empty($opt['product'])) — {{ Str::limit($opt['product'], 40) }}@endif</option>
                        @endif
                        @endforeach
                    </select>
                    <p class="text-muted small mb-0 mt-1">This client has multiple policies — choose which one this ticket relates to.</p>
                    @else
                    <input type="text" name="policy_number" id="policy_number" class="form-control font-monospace" value="{{ old('policy_number', $presetPolicy ?? '') }}" placeholder="Auto-fills when client selected">
                    @endif
                </div>
                <style>
                .contact-select-dropdown { max-height: 320px; overflow-y: auto; display: none; z-index: 1050; border: 1px solid var(--agile-border, #e2e8f0); background: #fff; }
                .contact-select-dropdown .contact-option { padding: 0.75rem 1rem; cursor: pointer; border: none; display: flex; align-items: center; }
                .contact-select-dropdown .contact-option:hover, .contact-select-dropdown .contact-option.active { background: var(--agile-primary-muted, rgba(26,70,138,0.08)); color: var(--agile-primary); }
                .contact-select-dropdown .contact-option .bi { flex-shrink: 0; }
                .contact-select-dropdown .contact-hint { padding: 0.5rem 1rem; font-size: 0.8rem; color: var(--agile-text-muted); background: #f8fafc; border-bottom: 1px solid #eee; }
                </style>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category" class="form-select">
                        <option value="">Select an Option</option>
                        @foreach(ticket_categories() as $cat)
                        <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="description" id="ticketDescriptionBox" class="form-control" rows="2" placeholder="Brief details if needed">{{ old('description', $presetDescription ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Auto-loaded client snapshot --}}
    <div class="app-card mb-4" id="clientSnapshotCard" @if(empty($presetClientDetails)) style="display:none" @endif>
        <div class="p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <p class="small fw-semibold text-muted mb-0 text-uppercase" style="letter-spacing:0.06em">Client details</p>
                    <p class="text-muted small mb-0" id="clientSnapshotHint">Loaded automatically when a prospect/client is selected.</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="clientSnapshotInsertBtn" title="Add a short client summary into the description">
                    <i class="bi bi-clipboard-plus me-1"></i>Add to description
                </button>
            </div>
            <div class="row g-3" id="clientSnapshotGrid">
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Name</span><span class="client-snap-value" data-snap="full_name">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Policy</span><span class="client-snap-value font-monospace" data-snap="policy_number">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Mobile</span><span class="client-snap-value" data-snap="mobile">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Phone</span><span class="client-snap-value" data-snap="phone">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Email</span><span class="client-snap-value" data-snap="email">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">ID / Passport</span><span class="client-snap-value font-monospace" data-snap="id_number">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">KRA PIN</span><span class="client-snap-value font-monospace" data-snap="kra_pin">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Product</span><span class="client-snap-value" data-snap="product">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">System</span><span class="client-snap-value" data-snap="system_label">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Status</span><span class="client-snap-value" data-snap="status">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">City</span><span class="client-snap-value" data-snap="city">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Intermediary</span><span class="client-snap-value" data-snap="intermediary">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Lead source</span><span class="client-snap-value" data-snap="leadsource">—</span></div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="client-snap-field"><span class="client-snap-label">Open tickets</span><span class="client-snap-value" data-snap="open_tickets">—</span></div>
                </div>
            </div>
        </div>
    </div>
    <style>
    .client-snap-field { display:flex; flex-direction:column; gap:0.15rem; padding:0.65rem 0.75rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; min-height:3.6rem; }
    .client-snap-label { font-size:0.65rem; font-weight:700; letter-spacing:0.05em; text-transform:uppercase; color:#64748b; }
    .client-snap-value { font-size:0.9rem; font-weight:600; color:#1e293b; word-break:break-word; }
    .client-snap-value.is-empty { color:#94a3b8; font-weight:500; }
    </style>
    <script type="application/json" id="presetClientDetailsData">@json($presetClientDetails ?? null)</script>

    <input type="hidden" name="status" value="Open">
    <input type="hidden" name="priority" value="Normal">
    @php
        $presetSource = ($fromServeClient ?? false) || $fromClientLanding ? 'Phone' : (($fromMailManager ?? false) ? 'Email' : 'CRM');
    @endphp

    {{-- Ticket source / product / severity before email options --}}
    <div class="app-card mb-4">
        <div class="p-4">
            <p class="small fw-semibold text-muted mb-3">Ticket Source, Product Line, Severity</p>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Ticket Source</label>
                    <select name="ticket_source" class="form-select">
                        <option value="">Select an Option</option>
                        @foreach(ticket_sources() as $src)
                        <option value="{{ $src }}" {{ old('ticket_source', $presetSource ?? '') == $src ? 'selected' : '' }}>{{ $src }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Product Line / Account</label>
                    <select name="organization_id" id="organizationSelect" class="form-select">
                        <option value="">— Select Product Line —</option>
                        @php
                            $productLines = collect($accounts ?? []);
                            if ($productLines->isEmpty()) {
                                $productLines = collect([
                                    (object)['accountid' => 'line:Individual Life', 'accountname' => 'Individual Life'],
                                    (object)['accountid' => 'line:Group Life', 'accountname' => 'Group Life'],
                                    (object)['accountid' => 'line:Credit Life', 'accountname' => 'Credit Life'],
                                    (object)['accountid' => 'line:Mortgage', 'accountname' => 'Mortgage'],
                                    (object)['accountid' => 'line:Group Last Expense', 'accountname' => 'Group Last Expense'],
                                ]);
                            }
                            $selectedOrg = old('organization_id', $presetOrganizationId ?? null);
                        @endphp
                        @foreach ($productLines as $a)
                        <option value="{{ $a->accountid ?? $a['accountid'] ?? '' }}" {{ $selectedOrg == ($a->accountid ?? $a['accountid'] ?? '') ? 'selected' : '' }}>{{ $a->accountname ?? $a['accountname'] ?? 'Option' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Severity</label>
                    <select name="severity" class="form-select">
                        <option value="">—</option>
                        <option value="Minor" {{ old('severity') == 'Minor' ? 'selected' : '' }}>Minor</option>
                        <option value="Major" {{ old('severity') == 'Major' ? 'selected' : '' }}>Major</option>
                        <option value="Critical" {{ old('severity') == 'Critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Email client last --}}
    <div class="app-card mb-4">
        <div class="p-4">
            <p class="small fw-semibold text-muted mb-3">Email client</p>
            <div class="form-check mb-2">
                <input type="checkbox" name="send_email_to_client" id="send_email_to_client" value="1" class="form-check-input" {{ old('send_email_to_client', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="send_email_to_client">Send email notification to client</label>
            </div>
            <div class="ms-4" id="client-email-options">
                <label class="form-label fw-normal text-muted small mb-1">Custom message for client email <span class="text-muted">(optional)</span></label>
                <textarea name="client_email_message" class="form-control form-control-sm" rows="3" placeholder="e.g. We will process your policy renewal notice within 3 business days." maxlength="2000">{{ old('client_email_message') }}</textarea>
                <p class="text-muted small mb-0 mt-1">Leave blank for the default message. When provided, this is inserted into the email sent to the client.</p>
            </div>
            <p class="text-muted small mb-0 mt-1">When checked, the client will receive an email with the ticket number after creation.</p>
        </div>
    </div>

    {{-- Knowledge base guidance while logging --}}
    <div class="app-card mb-4" id="createKbPanel" data-search-url="{{ route('support.faq.search') }}">
        <div class="p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <h6 class="mb-0 fw-bold"><i class="bi bi-journal-bookmark me-1 text-primary"></i>Resolution guidance (Knowledge Base)</h6>
                <a href="{{ route('support.faq') }}" class="small" target="_blank" rel="noopener">Browse FAQs</a>
            </div>
            <p class="text-muted small mb-3 mb-md-2">Search FAQs while logging the ticket. Add guidance into the description now, or resolve later from the ticket page with <strong>Use as solution</strong>.</p>
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="search" id="createKbSearch" class="form-control" placeholder="e.g. payment not reflected, portal login…" autocomplete="off">
                    </div>
                </div>
                <div class="col-lg-7">
                    <div id="createKbResults" class="small text-muted">Type a few words to search the knowledge base.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn app-btn-primary"><i class="bi bi-check-lg me-1"></i> Save Ticket</button>
        @if($returnPolicy)
        <a href="{{ route('support.clients.show', ['policy' => $returnPolicy]) }}" class="btn btn-outline-secondary">Back to Client</a>
        @elseif($fromServeClient ?? false)
        <a href="{{ route('support.customers') }}" class="btn btn-outline-secondary">Back to Clients</a>
        @elseif($fromMailManager ?? false)
        <a href="{{ route('tools.mail-manager') }}" class="btn btn-outline-secondary">Back to Mail Manager</a>
        @elseif($presetContactId ?? null)
        <a href="{{ route('contacts.show', $presetContactId) }}?tab=tickets" class="btn btn-outline-secondary">Cancel</a>
        @else
        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
        @endif
    </div>
</form>

<script id="clientsData" type="application/json">@json(collect($clients ?? [])->map(fn($c) => ['id' => $c->contactid, 'name' => trim(($c->firstname ?? '') . ' ' . ($c->lastname ?? '')) ?: 'Client #' . $c->contactid, 'policy' => $c->policy_number ?? '', 'phone' => $c->mobile ?? $c->phone ?? '', 'email' => personal_email_only($c->email ?? null) ?? ($c->email ?? '')])->values())</script>
<script>
(function() {
    const initialClients = JSON.parse(document.getElementById('clientsData').textContent || '[]');
    let clients = initialClients.slice();
    const searchInput = document.getElementById('contactSearch');
    const contactIdInput = document.getElementById('contactId');
    const dropdown = document.getElementById('contactDropdown');
    const browseBtn = document.getElementById('contactBrowse');
    const changeBtn = document.getElementById('contactChange');
    const hintEl = document.getElementById('contactSearchHint');
    const snapshotCard = document.getElementById('clientSnapshotCard');
    const snapshotHint = document.getElementById('clientSnapshotHint');
    const insertSnapBtn = document.getElementById('clientSnapshotInsertBtn');
    let lastSnapshot = null;
    let fetchTimer;
    let highlightedIdx = -1;
    let currentItems = [];
    let justSelected = false;

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function setSelectedMode(selected) {
        if (browseBtn) browseBtn.classList.toggle('d-none', selected);
        if (changeBtn) changeBtn.classList.toggle('d-none', !selected);
        if (hintEl) hintEl.textContent = selected ? 'Client selected. Click Change to pick a different one.' : 'Type to search or click Browse to select a client';
        if (searchInput && !searchInput.hasAttribute('data-locked')) searchInput.readOnly = selected;
    }
    function clearSnapshot() {
        lastSnapshot = null;
        if (snapshotCard) snapshotCard.style.display = 'none';
        document.querySelectorAll('[data-snap]').forEach(el => {
            el.textContent = '—';
            el.classList.add('is-empty');
        });
    }
    function fillSnapshot(d) {
        if (!d || typeof d !== 'object') { clearSnapshot(); return; }
        lastSnapshot = d;
        if (snapshotCard) snapshotCard.style.display = '';
        if (snapshotHint) {
            const src = d.source === 'local' ? 'local client record' : 'CRM prospect record';
            snapshotHint.textContent = 'Loaded from ' + src + (d.open_tickets ? (' · ' + d.open_tickets + ' open ticket(s)') : '');
        }
        const map = {
            full_name: d.full_name,
            policy_number: d.policy_number,
            mobile: d.mobile || d.phone,
            phone: d.phone,
            email: d.email,
            id_number: d.id_number,
            kra_pin: d.kra_pin,
            product: d.product,
            system_label: d.system_label || d.system,
            status: d.status,
            city: d.city,
            intermediary: d.intermediary,
            leadsource: d.leadsource,
            open_tickets: (d.open_tickets != null && d.open_tickets !== '') ? String(d.open_tickets) : ''
        };
        Object.keys(map).forEach(key => {
            const el = document.querySelector('[data-snap="' + key + '"]');
            if (!el) return;
            const val = (map[key] == null || String(map[key]).trim() === '') ? '' : String(map[key]).trim();
            el.textContent = val || '—';
            el.classList.toggle('is-empty', !val);
        });

        // Suggest product line from client system when empty
        const orgSelect = document.getElementById('organizationSelect');
        if (orgSelect && !orgSelect.value && d.system_label) {
            const label = String(d.system_label).toLowerCase();
            Array.from(orgSelect.options).forEach(opt => {
                const t = (opt.textContent || '').toLowerCase();
                if (!orgSelect.value && t && label && (t.includes(label) || label.includes(t.replace(/\s*life\s*$/, '').trim()))) {
                    orgSelect.value = opt.value;
                }
            });
        }
    }
    function loadClientDetails(contactId, fallbackPolicy) {
        if (!contactId) { clearSnapshot(); return; }
        if (snapshotHint) snapshotHint.textContent = 'Loading client details…';
        if (snapshotCard) snapshotCard.style.display = '';
        fetch('{{ route("api.tickets.contact.policy", ["contact" => ":id"]) }}'.replace(':id', contactId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(d => {
            const policyInput = document.getElementById('policy_number') || document.querySelector('[name="policy_number"]');
            const policy = (d.policy_number || d.policy || fallbackPolicy || '').trim();
            if (policyInput && policyInput.tagName === 'INPUT' && policy) {
                policyInput.value = policy;
            }
            fillSnapshot(d);
        })
        .catch(() => {
            if (snapshotHint) snapshotHint.textContent = 'Could not load extra client details.';
        });
    }
    function renderDropdown(items, isLoading, noResults) {
        currentItems = items || [];
        highlightedIdx = -1;
        if (isLoading) {
            dropdown.innerHTML = '<div class="list-group-item text-muted py-4 text-center"><span class="spinner-border spinner-border-sm me-2"></span>Loading clients...</div>';
            dropdown.style.display = 'block';
        } else if (noResults || (currentItems.length === 0 && (searchInput.value || '').trim().length >= 1)) {
            dropdown.innerHTML = '<div class="list-group-item text-muted py-4 text-center">No prospects found. Try a different search or <a href="{{ route("contacts.create") }}" class="text-primary">add a new prospect</a>.</div>';
            dropdown.style.display = 'block';
        } else if (currentItems.length) {
            dropdown.innerHTML = currentItems.slice(0, 60).map((c, i) => {
                const policy = String(c.policy || c.policy_number || '').trim();
                const meta = [policy, c.phone, c.email].filter(Boolean).map(escapeHtml).join(' · ');
                return `<a href="#" class="list-group-item list-group-item-action py-2" data-id="${c.id}" data-name="${escapeHtml(c.name)}" data-policy="${escapeHtml(policy)}" role="option" data-index="${i}"><i class="bi bi-person me-2 text-muted"></i><span><span class="fw-semibold">${escapeHtml(c.name)}</span>${meta ? `<span class="d-block small text-muted">${meta}</span>` : ''}</span></a>`;
            }).join('');
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
        searchInput.setAttribute('aria-expanded', dropdown.style.display === 'block');
    }
    function renderFromLocal() {
        const term = (searchInput.value || '').trim().toLowerCase();
        const filtered = term ? clients.filter(c => (c.name || '').toLowerCase().includes(term) || String(c.policy || '').toLowerCase().includes(term) || String(c.phone || '').includes(term)) : clients.slice(0, 100);
        renderDropdown(filtered);
    }
    function selectItem(item) {
        if (!item) return;
        justSelected = true;
        contactIdInput.value = item.dataset.id;
        searchInput.value = item.dataset.name;
        const policyInput = document.getElementById('policy_number') || document.querySelector('[name="policy_number"]');
        const policy = (item.dataset.policy || '').trim();
        if (policyInput && policyInput.tagName === 'INPUT' && policy) {
            policyInput.value = policy;
        }
        loadClientDetails(item.dataset.id, policy);
        dropdown.style.display = 'none';
        searchInput.setAttribute('aria-expanded', 'false');
        setSelectedMode(true);
    }
    function fetchContacts(browseMode) {
        const q = searchInput.value.trim();
        const url = browseMode || q === '' ? '{{ route("api.tickets.contacts") }}?browse=1&limit=100' : '{{ route("api.tickets.contacts") }}?q=' + encodeURIComponent(q) + '&limit=60';
        renderDropdown(null, true);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (Array.isArray(data) && data.length) { clients = data; renderDropdown(data); }
            else if (!browseMode && q) renderDropdown([], false, true);
            else renderDropdown([], false, true);
        })
        .catch(() => renderFromLocal());
    }

    if (!searchInput || !contactIdInput || !dropdown) return;

    searchInput.addEventListener('focus', function() {
        if (this.readOnly) return;
        if (justSelected) { justSelected = false; return; }
        if (contactIdInput.value) return;
        clearTimeout(fetchTimer);
        const q = this.value.trim();
        if (q.length >= 1) fetchTimer = setTimeout(() => fetchContacts(false), 100);
        else fetchContacts(true);
    });
    searchInput.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(fetchTimer);
        setSelectedMode(false);
        contactIdInput.value = '';
        clearSnapshot();
        const policyInput = document.getElementById('policy_number') || document.querySelector('input[name="policy_number"]');
        if (policyInput) policyInput.value = '';
        if (q.length >= 1) fetchTimer = setTimeout(() => fetchContacts(false), 200);
        else { clients = initialClients.slice(); renderFromLocal(); }
    });
    searchInput.addEventListener('keydown', function(e) {
        if (dropdown.style.display !== 'block' || !currentItems.length) return;
        const opts = dropdown.querySelectorAll('[data-id]');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightedIdx = Math.min(highlightedIdx + 1, opts.length - 1);
            opts[highlightedIdx]?.scrollIntoView({ block: 'nearest' });
            opts.forEach((o, i) => o.classList.toggle('active', i === highlightedIdx));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightedIdx = Math.max(highlightedIdx - 1, 0);
            opts[highlightedIdx]?.scrollIntoView({ block: 'nearest' });
            opts.forEach((o, i) => o.classList.toggle('active', i === highlightedIdx));
        } else if (e.key === 'Enter' && highlightedIdx >= 0 && opts[highlightedIdx]) {
            e.preventDefault();
            selectItem(opts[highlightedIdx]);
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            searchInput.setAttribute('aria-expanded', 'false');
        }
    });
    searchInput.addEventListener('blur', () => setTimeout(() => {
        if (document.activeElement?.closest('#contactSearchWrapper')) return;
        dropdown.style.display = 'none';
        searchInput.setAttribute('aria-expanded', 'false');
    }, 180));

    dropdown.addEventListener('mousedown', (e) => {
        const item = e.target.closest('[data-id]');
        if (item) {
            e.preventDefault();
            selectItem(item);
            setSelectedMode(true);
        }
    });

    if (browseBtn) {
        browseBtn.addEventListener('click', function() {
            searchInput.focus();
            fetchContacts(true);
        });
    }
    if (changeBtn) {
        changeBtn.addEventListener('click', function() {
            contactIdInput.value = '';
            searchInput.value = '';
            clearSnapshot();
            const policyInput = document.getElementById('policy_number') || document.querySelector('input[name="policy_number"]');
            if (policyInput && policyInput.tagName === 'INPUT') policyInput.value = '';
            setSelectedMode(false);
            searchInput.focus();
            fetchContacts(true);
        });
    }

    if (insertSnapBtn) {
        insertSnapBtn.addEventListener('click', function () {
            if (!lastSnapshot) return;
            const d = lastSnapshot;
            const lines = [
                'Client: ' + (d.full_name || '—'),
                d.policy_number ? ('Policy: ' + d.policy_number) : null,
                (d.mobile || d.phone) ? ('Phone: ' + (d.mobile || d.phone)) : null,
                d.email ? ('Email: ' + d.email) : null,
                d.id_number ? ('ID: ' + d.id_number) : null,
                d.product ? ('Product: ' + d.product) : null,
                d.system_label ? ('System: ' + d.system_label) : null,
            ].filter(Boolean).join('\n');
            const box = document.getElementById('ticketDescriptionBox');
            if (!box || !lines) return;
            box.value = box.value ? (box.value.replace(/\s*$/, '') + '\n\n' + lines) : lines;
            box.focus();
        });
    }

    const oldId = contactIdInput.value;
    const presetSnapEl = document.getElementById('presetClientDetailsData');
    let presetSnap = null;
    try { presetSnap = JSON.parse(presetSnapEl ? presetSnapEl.textContent : 'null'); } catch (e) { presetSnap = null; }
    if (oldId) {
        const c = clients.find(x => String(x.id) === String(oldId));
        if (c) searchInput.value = c.name;
        setSelectedMode(true);
        if (presetSnap) {
            fillSnapshot(presetSnap);
            const policyInput = document.getElementById('policy_number') || document.querySelector('input[name="policy_number"]');
            if (policyInput && policyInput.tagName === 'INPUT' && !policyInput.value && presetSnap.policy_number) {
                policyInput.value = presetSnap.policy_number;
            }
        } else {
            loadClientDetails(oldId);
        }
    } else if (presetSnap) {
        fillSnapshot(presetSnap);
    }

    const sendEmailCb = document.getElementById('send_email_to_client');
    const emailOptions = document.getElementById('client-email-options');
    if (sendEmailCb && emailOptions) {
        function toggleEmailOptions() {
            emailOptions.style.opacity = sendEmailCb.checked ? '1' : '0.5';
            emailOptions.style.pointerEvents = sendEmailCb.checked ? 'auto' : 'none';
        }
        sendEmailCb.addEventListener('change', toggleEmailOptions);
        toggleEmailOptions();
    }
})();
</script>
<script>
(function () {
    const panel = document.getElementById('createKbPanel');
    if (!panel) return;
    const url = panel.getAttribute('data-search-url');
    const input = document.getElementById('createKbSearch');
    const box = document.getElementById('createKbResults');
    const titleInput = document.querySelector('input[name="title"]');

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function render(rows) {
        if (!rows.length) {
            box.innerHTML = '<span class="text-muted">No matching FAQs.</span>';
            return;
        }
        const store = {};
        rows.forEach(r => store[r.id] = r);
        box.innerHTML = rows.slice(0, 4).map(r => `
            <div class="border rounded p-2 mb-2 bg-white">
                <div class="text-uppercase text-muted" style="font-size:.65rem">${escapeHtml(r.category)}</div>
                <div class="fw-semibold mb-1">${escapeHtml(r.question)}</div>
                <div class="text-muted mb-2" style="font-size:.8rem;max-height:3.2rem;overflow:hidden">${escapeHtml(r.answer)}</div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-id="${r.id}"><i class="bi bi-plus-lg me-1"></i>Add to description</button>
            </div>
        `).join('');
        box.querySelectorAll('button[data-id]').forEach(btn => {
            btn.addEventListener('click', function () {
                const r = store[this.getAttribute('data-id')];
                if (!r) return;
                const desc = document.getElementById('ticketDescriptionBox');
                if (!desc) return;
                const block = 'KB guidance — ' + r.question + '\n' + r.answer;
                desc.value = desc.value ? (desc.value.replace(/\s*$/, '') + '\n\n' + block) : block;
                desc.focus();
            });
        });
    }

    let t;
    function doSearch(q) {
        if (!q || q.trim().length < 2) {
            box.innerHTML = '<span class="text-muted">Type a few words to search the knowledge base.</span>';
            return;
        }
        fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => render((data && data.results) || []))
            .catch(() => { box.innerHTML = '<span class="text-muted">Could not load FAQs.</span>'; });
    }

    input.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(() => doSearch(this.value.trim()), 250);
    });
    if (titleInput) {
        titleInput.addEventListener('change', function () {
            if (!input.value.trim() && this.value.trim().length >= 4) {
                input.value = this.value.trim();
                doSearch(this.value.trim());
            }
        });
    }
})();
</script>

@endsection
