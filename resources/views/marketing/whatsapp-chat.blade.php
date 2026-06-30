@extends('layouts.app')

@section('title', 'Kenya Orient WhatsApp')

@section('content')
@php
    $companyName = $company['name'] ?? config('app.name');
    $companyPhone = $company['display_phone'] ?? $company['phone'] ?? null;
@endphp

<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="page-title"><i class="bi bi-whatsapp text-success me-2"></i>Kenya Orient WhatsApp</h1>
        <p class="page-subtitle mb-0">
            Reply to customers from Kenya Orient WhatsApp
            @if($companyPhone)
                <strong class="text-success">{{ $companyPhone }}</strong>
            @endif
        </p>
    </div>
    @if($configured ?? false)
    <span class="badge {{ ($sandbox ?? false) ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} px-3 py-2">
        @if($sandbox ?? false)
        <i class="bi bi-flask me-1"></i>Sandbox mode
        @else
        <i class="bi bi-check-circle-fill me-1"></i>Connected
        @endif
    </span>
    @endif
</div>

@if($sandbox ?? false)
<div class="alert alert-warning border-warning d-flex flex-wrap align-items-start gap-3 mb-3">
    <i class="bi bi-flask fs-4"></i>
    <div class="flex-grow-1">
        <strong>Sandbox mode</strong> — messages are stored locally only. No Meta WhatsApp API calls are made.
        Each new customer message auto-creates a <strong>HelpDesk ticket</strong> (source WHATSAPP) and sends a ticket-number reply in the chat.
        Set <code>WHATSAPP_SANDBOX=false</code> when you connect the real company WhatsApp.
    </div>
</div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(!($configured ?? false))
<div class="card p-4 mb-4 border-warning">
    <h5 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Kenya Orient WhatsApp not connected</h5>
    <p class="text-muted mb-3">Connect Meta WhatsApp Cloud API, or enable <strong>sandbox mode</strong> to test chat locally.</p>
    <div class="card bg-light border-0 p-3 mb-3">
        <p class="fw-semibold mb-2"><i class="bi bi-flask me-1"></i>Quick start — sandbox (no Meta account)</p>
        <p class="small text-muted mb-2">Add to <code>.env</code>:</p>
        <pre class="small mb-2 bg-white border rounded p-2 mb-0">WHATSAPP_ENABLED=true
WHATSAPP_SANDBOX=true</pre>
        <p class="small text-muted mb-0">Then run <code>php artisan config:clear</code> and refresh this page.</p>
    </div>
    <p class="fw-semibold small mb-1">Production (real company number)</p>
    <ol class="small text-muted mb-3">
        <li>In Meta Business Suite, open <strong>WhatsApp → API Setup</strong> and copy Phone number ID, WABA ID, and permanent access token.</li>
        <li>Add to <code>.env</code>: <code>WHATSAPP_ENABLED=true</code>, <code>WHATSAPP_SANDBOX=false</code>, <code>WHATSAPP_PHONE_NUMBER_ID</code>, <code>WHATSAPP_ACCESS_TOKEN</code>, <code>WHATSAPP_DISPLAY_PHONE=+254…</code></li>
        <li>On your Meta app webhook, subscribe to <strong>messages</strong> (same URL as Facebook: <code>/webhooks/social/meta</code>).</li>
        <li>Run <code>php artisan config:clear</code></li>
    </ol>
    <p class="mb-0 small">{{ $setupInstructions ?? '' }}</p>
</div>
@else

@if($sandbox ?? false)
<div class="card p-3 mb-3 border-warning">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-2"><i class="bi bi-person-down me-1"></i>Simulate customer message</h6>
            <p class="text-muted small mb-0">Pretend a customer texted your company WhatsApp — the message appears in the inbox like a real webhook.</p>
        </div>
    </div>
    <form id="waSimulateForm" class="row g-2 mt-2" method="POST" action="{{ route('marketing.whatsapp.simulate-inbound') }}">
        @csrf
        <div class="col-md-3">
            <label class="form-label small mb-1">Customer phone</label>
            <input type="text" name="phone" class="form-control form-control-sm" value="{{ $activePhone ?: '254712345678' }}" placeholder="254712345678" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Customer name</label>
            <input type="text" name="contact_name" class="form-control form-control-sm" placeholder="Jane Customer">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Message</label>
            <input type="text" name="message" class="form-control form-control-sm" placeholder="Hello, I need help with my policy" required maxlength="4096">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-warning btn-sm w-100"><i class="bi bi-arrow-down-circle me-1"></i>Simulate</button>
        </div>
    </form>
</div>
@endif

<div class="wa-chat-shell card overflow-hidden">
    <div class="wa-chat-layout">
        {{-- Conversation list --}}
        <aside class="wa-chat-sidebar">
            <div class="wa-sidebar-header">
                <div class="wa-company-badge">
                    <div class="wa-company-avatar"><i class="bi bi-building"></i></div>
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate">{{ $companyName }}</div>
                        <small class="text-muted">{{ $companyPhone ?: 'Business account' }}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#newChatModal" title="New chat">
                    <i class="bi bi-chat-dots"></i>
                </button>
            </div>
            <div class="wa-sidebar-search px-3 pb-2">
                <input type="search" id="waSearch" class="form-control form-control-sm" placeholder="Search chats…">
            </div>
            <div class="wa-conversation-list" id="waConversationList">
                @forelse($conversations as $conv)
                <a href="{{ route('marketing.whatsapp', ['phone' => $conv['phone']]) }}"
                   class="wa-conversation-item {{ ($activePhone ?? '') === $conv['phone'] ? 'active' : '' }}"
                   data-phone="{{ $conv['phone'] }}"
                   data-search="{{ strtolower(($conv['name'] ?? '') . ' ' . $conv['phone']) }}">
                    <div class="wa-conv-avatar">{{ strtoupper(substr($conv['name'] ?: $conv['phone'], 0, 1)) }}</div>
                    <div class="wa-conv-body min-w-0">
                        <div class="d-flex justify-content-between gap-2">
                            <strong class="text-truncate">{{ $conv['name'] ?: ($conv['display_phone'] ?? $conv['phone']) }}</strong>
                            <small class="text-muted flex-shrink-0">{{ $conv['last_at_human'] }}</small>
                        </div>
                        <div class="text-muted small text-truncate">{{ Str::limit($conv['preview'], 48) }}</div>
                        @if(!empty($conv['ticket_no']))
                        <span class="badge bg-primary mt-1">{{ $conv['ticket_no'] }}</span>
                        @endif
                    </div>
                </a>
                @empty
                <div class="text-center text-muted small p-4">
                    <i class="bi bi-inbox d-block mb-2" style="font-size:1.5rem;"></i>
                    No conversations yet. Customer messages to your company number will appear here.
                </div>
                @endforelse
            </div>
        </aside>

        {{-- Thread --}}
        <section class="wa-chat-main">
            @if($activePhone && $activeContact)
            <div class="wa-thread-header">
                <div class="wa-conv-avatar">{{ strtoupper(substr($activeContact['name'] ?: $activePhone, 0, 1)) }}</div>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-bold">{{ $activeContact['name'] ?: ($activeContact['display_phone'] ?? $activePhone) }}</div>
                    <small class="text-muted">{{ $activeContact['display_phone'] ?? $activePhone }}</small>
                </div>
                @if(!empty($activeTicket['ticket_id'] ?? null))
                <a href="{{ route('tickets.show', $activeTicket['ticket_id']) }}" class="btn btn-sm btn-outline-light" target="_blank" rel="noopener">
                    <i class="bi bi-ticket-perforated me-1"></i>{{ $activeTicket['ticket_no'] ?? 'Ticket' }}
                </a>
                @endif
            </div>

            <div class="wa-messages" id="waMessages" data-phone="{{ $activePhone }}">
                @foreach($messages as $msg)
                <div class="wa-msg-row wa-msg-{{ $msg['direction'] }}" data-id="{{ $msg['id'] }}" data-at="{{ $msg['at'] }}">
                    <div class="wa-bubble">
                        <div class="wa-bubble-text">{!! nl2br(e($msg['body'])) !!}</div>
                        <div class="wa-bubble-meta">
                            {{ $msg['at_display'] }}
                            @if($msg['direction'] === 'outbound')
                                @if(($msg['delivery_status'] ?? '') === 'read')
                                    <i class="bi bi-check2-all text-info"></i>
                                @elseif(($msg['delivery_status'] ?? '') === 'delivered')
                                    <i class="bi bi-check2-all"></i>
                                @else
                                    <i class="bi bi-check2"></i>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <form class="wa-composer" id="waSendForm" method="POST" action="{{ route('marketing.whatsapp.send') }}">
                @csrf
                <input type="hidden" name="phone" value="{{ $activePhone }}">
                <textarea name="message" id="waMessageInput" class="form-control" rows="2" placeholder="Reply from Kenya Orient WhatsApp…" required maxlength="4096"></textarea>
                <button type="submit" class="btn btn-success wa-send-btn" id="waSendBtn">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            @else
            <div class="wa-empty-thread">
                <div class="text-center">
                    <div class="wa-empty-icon"><i class="bi bi-whatsapp"></i></div>
                    <h5 class="fw-bold mt-3">Kenya Orient WhatsApp inbox</h5>
                    <p class="text-muted mb-3">Select a conversation or start a new chat.<br>Messages send from <strong>{{ $companyPhone ?: 'Kenya Orient WhatsApp' }}</strong>.</p>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newChatModal">
                        <i class="bi bi-chat-dots me-1"></i>New chat
                    </button>
                </div>
            </div>
            @endif
        </section>
    </div>
</div>

{{-- New chat modal --}}
<div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ route('marketing.whatsapp') }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="newChatModalLabel">New WhatsApp chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Customer phone number</label>
                    <input type="text" name="phone" class="form-control" placeholder="254712345678 or 0712345678" required>
                    <small class="text-muted">Customer must have messaged your business number first (in production), or use sandbox simulate.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Open chat</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
.wa-chat-shell { border: 1px solid var(--card-border, rgba(14,67,133,0.12)); border-radius: 16px; min-height: 70vh; }
.wa-chat-layout { display: flex; min-height: 70vh; }
.wa-chat-sidebar { width: 340px; max-width: 40%; border-right: 1px solid var(--card-border, rgba(14,67,133,0.12)); display: flex; flex-direction: column; background: #f8fafc; }
.wa-sidebar-header { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 1rem; border-bottom: 1px solid var(--card-border, rgba(14,67,133,0.12)); background: #075e54; color: #fff; }
.wa-company-badge { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
.wa-company-avatar, .wa-conv-avatar { width: 42px; height: 42px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
.wa-conv-avatar { background: #25d366; color: #fff; font-size: 0.95rem; }
.wa-sidebar-header small { color: rgba(255,255,255,0.75) !important; }
.wa-conversation-list { overflow-y: auto; flex: 1; }
.wa-conversation-item { display: flex; gap: 0.75rem; padding: 0.85rem 1rem; text-decoration: none; color: inherit; border-bottom: 1px solid rgba(0,0,0,0.04); transition: background 0.15s; }
.wa-conversation-item:hover { background: rgba(37,211,102,0.08); color: inherit; }
.wa-conversation-item.active { background: rgba(37,211,102,0.15); border-left: 3px solid #25d366; }
.wa-chat-main { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #ece5dd; background-image: radial-gradient(rgba(0,0,0,0.03) 1px, transparent 1px); background-size: 12px 12px; }
.wa-thread-header { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1.25rem; background: #075e54; color: #fff; }
.wa-thread-header small { color: rgba(255,255,255,0.75) !important; }
.wa-messages { flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; }
.wa-msg-row { display: flex; }
.wa-msg-inbound { justify-content: flex-start; }
.wa-msg-outbound { justify-content: flex-end; }
.wa-bubble { max-width: min(75%, 520px); padding: 0.55rem 0.75rem; border-radius: 12px; box-shadow: 0 1px 1px rgba(0,0,0,0.08); }
.wa-msg-inbound .wa-bubble { background: #fff; border-top-left-radius: 4px; }
.wa-msg-outbound .wa-bubble { background: #dcf8c6; border-top-right-radius: 4px; }
.wa-bubble-meta { font-size: 0.68rem; color: #667781; text-align: right; margin-top: 0.25rem; display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem; }
.wa-composer { display: flex; gap: 0.5rem; align-items: flex-end; padding: 0.75rem 1rem; background: #f0f2f5; border-top: 1px solid rgba(0,0,0,0.06); }
.wa-composer textarea { resize: none; border-radius: 24px; border: none; padding: 0.65rem 1rem; }
.wa-send-btn { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.wa-empty-thread { flex: 1; display: flex; align-items: center; justify-content: center; }
.wa-empty-icon { width: 88px; height: 88px; border-radius: 50%; background: rgba(37,211,102,0.15); color: #25d366; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto; }
@media (max-width: 991px) {
    .wa-chat-layout { flex-direction: column; }
    .wa-chat-sidebar { width: 100%; max-width: none; max-height: 40vh; border-right: none; border-bottom: 1px solid var(--card-border); }
}
</style>

@if($configured ?? false)
<script>
(function () {
    var messagesEl = document.getElementById('waMessages');
    var form = document.getElementById('waSendForm');
    var input = document.getElementById('waMessageInput');
    var search = document.getElementById('waSearch');

    function scrollToBottom() {
        if (!messagesEl) return;
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    scrollToBottom();

    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('.wa-conversation-item').forEach(function (el) {
                var hay = el.getAttribute('data-search') || '';
                el.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }

    function appendMessage(msg) {
        if (!messagesEl || !msg) return;
        if (document.querySelector('[data-id="' + msg.id + '"]')) return;
        var row = document.createElement('div');
        row.className = 'wa-msg-row wa-msg-' + msg.direction;
        row.setAttribute('data-id', msg.id);
        row.setAttribute('data-at', msg.at || '');
        var bubble = document.createElement('div');
        bubble.className = 'wa-bubble';
        bubble.innerHTML = '<div class="wa-bubble-text"></div><div class="wa-bubble-meta">' + (msg.at_display || '') + '</div>';
        bubble.querySelector('.wa-bubble-text').textContent = msg.body || '';
        row.appendChild(bubble);
        messagesEl.appendChild(row);
        scrollToBottom();
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('waSendBtn');
            if (btn) btn.disabled = true;
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
              .then(function (res) {
                if (res.ok && res.json.message) {
                    appendMessage(res.json.message);
                    input.value = '';
                } else {
                    alert(res.json.error || 'Failed to send message');
                }
              }).catch(function () { alert('Failed to send message'); })
              .finally(function () { if (btn) btn.disabled = false; });
        });
        input && input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
        });
    }

    if (messagesEl) {
        var phone = messagesEl.getAttribute('data-phone');
        setInterval(function () {
            var last = messagesEl.querySelector('.wa-msg-row:last-child');
            var since = last ? last.getAttribute('data-at') : '';
            var url = '{{ route('marketing.whatsapp.poll') }}?phone=' + encodeURIComponent(phone) + (since ? '&since=' + encodeURIComponent(since) : '');
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok || !data.messages) return;
                    data.messages.forEach(appendMessage);
                }).catch(function () {});
        }, 15000);
    }

    var simForm = document.getElementById('waSimulateForm');
    if (simForm) {
        simForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = simForm.querySelector('button[type=submit]');
            if (btn) btn.disabled = true;
            fetch(simForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': simForm.querySelector('[name=_token]').value,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(simForm)
            }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
              .then(function (res) {
                if (res.ok && res.json.phone) {
                    window.location.href = '{{ route('marketing.whatsapp') }}?phone=' + encodeURIComponent(res.json.phone);
                } else {
                    alert(res.json.error || 'Simulate failed');
                }
              }).catch(function () { alert('Simulate failed'); })
              .finally(function () { if (btn) btn.disabled = false; });
        });
    }
})();
</script>
@endif
@endsection
