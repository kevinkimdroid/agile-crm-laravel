@extends('layouts.app')

@section('title', ($ticket->title ?? 'Ticket') . ' — Ticket')

@section('content')
    @php
        $ticketPolicy = pick_policy_excluding_pin($ticket->cf_860 ?? null, $ticket->cf_856 ?? null, $ticket->cf_872 ?? null);
        if (! $ticketPolicy && ! empty($ticket->description ?? '') && preg_match('/Related policy:\s*([^\n]+)/i', (string) $ticket->description, $m)) {
            $p = trim($m[1]);
            $cid = (string) ($ticket->contact_id ?? '');
            if ($p !== '' && $p !== $cid && ! looks_like_kra_pin($p) && ! looks_like_client_id($p)) {
                $ticketPolicy = $p;
            }
        }
    @endphp
<nav class="breadcrumb-nav mb-3">
    <a href="{{ route('tickets.index') }}" class="text-muted small text-decoration-none">Tickets</a>
    <span class="text-muted mx-2">/</span>
    @if($ticketPolicy)
    <a href="{{ route('support.clients.show', ['policy' => $ticketPolicy, 'tab' => 'tickets']) }}" class="text-muted small text-decoration-none">Client</a>
    <span class="text-muted mx-2">/</span>
    @elseif($ticket->contact_id ?? null)
    <a href="{{ route('contacts.show', $ticket->contact_id) }}?tab=tickets" class="text-muted small text-decoration-none">Prospect</a>
    <span class="text-muted mx-2">/</span>
    @endif
    <span class="text-dark small fw-semibold">{{ $ticket->ticket_no ?? $ticket->ticketid }}</span>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="app-page-title mb-2">{{ $ticket->title ?? 'Untitled Ticket' }}</h1>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="ticket-status-badge ticket-status-{{ Str::slug($ticket->status ?? '') }}">{{ $ticket->status ?? '—' }}</span>
            @if($ticket->priority ?? null)
            <span class="text-muted small">• {{ $ticket->priority }} priority</span>
            @endif
            @if($ticketPolicy)
            <span class="text-muted small font-monospace">• {{ $ticketPolicy }}</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($ticketPolicy)
        <a href="{{ route('support.clients.show', ['policy' => $ticketPolicy, 'tab' => 'tickets']) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-person me-1"></i> View Client
        </a>
        @elseif($ticket->contact_id ?? null)
        <a href="{{ route('contacts.show', $ticket->contact_id) }}?tab=tickets" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-person me-1"></i> View Prospect
        </a>
        @endif
        @if($ticket->contact_id ?? null)
        <a href="{{ route('support.sms-notifier', array_filter(['contact_id' => $ticket->contact_id, 'return_policy' => $ticketPolicy])) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-chat-dots me-1"></i> Send SMS
        </a>
        @endif
        @if(($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true))
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#closeTicketModal">
            <i class="bi bi-check-circle me-1"></i> Close Ticket
        </button>
        @endif
        @if(($ticket->status ?? '') !== 'Inactive')
        <form action="{{ route('tickets.inactivate', $ticket->ticketid) }}" method="POST" onsubmit="return confirm('Inactivate this ticket? It will no longer appear in active lists.');" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Inactivate ticket"><i class="bi bi-pause-circle me-1"></i> Inactivate</button>
        </form>
        @endif
        <a href="{{ route('tickets.edit', $ticket->ticketid) }}" class="btn btn-sm app-btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Ticket workflow: Created by → Assigned to → Closed by --}}
<div class="ticket-workflow mb-4">
    <div class="ticket-workflow-step">
        <span class="ticket-workflow-label">Created by</span>
        <span class="ticket-workflow-value">{{ $ticket->created_by_name ?? '—' }}</span>
    </div>
    <i class="bi bi-arrow-right ticket-workflow-arrow"></i>
    <div class="ticket-workflow-step">
        <span class="ticket-workflow-label">Assigned to</span>
        <span class="ticket-workflow-value">{{ $ticket->assigned_to_name ?? '—' }}</span>
    </div>
    @if(($ticket->status ?? '') === 'Closed')
    <i class="bi bi-arrow-right ticket-workflow-arrow"></i>
    <div class="ticket-workflow-step">
        <span class="ticket-workflow-label">Closed by</span>
        <span class="ticket-workflow-value">{{ $ticket->closed_by_name ?? '—' }}</span>
    </div>
    @endif
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="app-card p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Description Details</h6>
            <div class="ticket-description">{{ $ticket->description ? nl2br(e(preg_replace("/\n{2,}/", "\n", $ticket->description))) : 'No description.' }}</div>
        </div>
        @if($ticket->solution ?? null)
        <div class="app-card p-4 mt-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Ticket Resolution</h6>
            <div class="ticket-description">{{ nl2br(e(preg_replace("/\n{2,}/", "\n", $ticket->solution ?? ''))) }}</div>
        </div>
        @elseif(($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true))
        <div class="app-card p-4 mt-4 border border-success">
            <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Use the <strong>Knowledge Base</strong> panel → <em>Use as solution</em>, then close the ticket.</p>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#closeTicketModal"><i class="bi bi-check-circle me-1"></i> Close Ticket</button>
        </div>
        @elseif(($ticket->status ?? '') !== 'Closed' && !($canCloseTickets ?? true))
        <div class="app-card p-4 mt-4 border border-warning">
            <p class="text-muted small mb-0"><i class="bi bi-lock me-1"></i>Only certain roles or the ticket assignee can close this ticket.</p>
        </div>
        @endif
        @if(($ticket->status ?? '') === 'Closed' && ($feedback ?? null))
        <div class="app-card p-4 mt-4 border-start border-4 border-success">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Client Feedback</h6>
            <p class="mb-2">
                <strong>Rating:</strong>
                @if($feedback->rating === 'happy')
                    <span class="text-success"><i class="bi bi-emoji-smile me-1"></i>Happy with the service</span>
                @else
                    <span class="text-warning"><i class="bi bi-emoji-frown me-1"></i>Not satisfied</span>
                @endif
            </p>
            @if($feedback->comment)
            <p class="text-muted small mb-0"><strong>Comment:</strong> {{ e($feedback->comment) }}</p>
            @endif
            <p class="text-muted small mt-2 mb-0">Submitted {{ $feedback->created_at?->diffForHumans() ?? '' }}</p>
        </div>
        @endif

        {{-- Comments / Further details --}}
        <div class="app-card p-4 mt-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em"><i class="bi bi-chat-text me-1"></i>Comments & Further Details</h6>
            <form method="POST" action="{{ route('tickets.comments.store', $ticket->ticketid) }}" class="mb-4">
                @csrf
                <label class="form-label fw-semibold">Add a comment</label>
                <textarea name="body" id="ticketCommentBox" class="form-control mb-2" rows="3" placeholder="Add details, updates, or notes about this ticket..." required>{{ old('body') }}</textarea>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Comment</button>
                @error('body')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
            @forelse($comments ?? [] as $comment)
            <div class="ticket-comment mb-4 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:var(--agile-border)!important">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="fw-semibold small">{{ e($comment->author_display) }}</span>
                    <span class="text-muted small">{{ $comment->created_at?->format('d M Y, H:i') ?? '' }}</span>
                </div>
                <div class="ticket-comment-body">{{ nl2br(e($comment->body)) }}</div>
            </div>
            @empty
            <p class="text-muted small mb-0">No comments yet. Add one above to record updates or further details.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="app-card p-4 mb-4" id="kbPanel"
             data-search-url="{{ route('support.faq.search') }}"
             data-mark-url="{{ route('support.faq.helpful') }}"
             data-ticket-title="{{ $ticket->title ?? '' }}"
             data-ticket-category="{{ $ticket->category ?? '' }}"
             data-can-close="{{ (($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true)) ? '1' : '0' }}">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="text-uppercase small fw-bold mb-0" style="color:var(--agile-primary);letter-spacing:0.08em"><i class="bi bi-journal-bookmark me-1"></i>Knowledge Base</h6>
                <a href="{{ route('support.faq') }}" class="small text-decoration-none" target="_blank" rel="noopener">Open FAQs</a>
            </div>
            <ol class="kb-steps small text-muted mb-3 ps-3">
                <li>Search FAQs for this issue</li>
                <li>Insert guidance into a comment, or</li>
                <li><strong>Use as solution</strong> then close the ticket</li>
            </ol>
            <div class="input-group input-group-sm mb-3">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="search" id="kbSearchInput" class="form-control" placeholder="Search FAQs…" autocomplete="off">
            </div>
            <div id="kbResults" class="kb-results"><p class="text-muted small mb-0">Loading suggested FAQs…</p></div>
        </div>
        <div class="app-card p-4 mb-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em"><i class="bi bi-person-badge me-1"></i>Reassignment Trail</h6>
            @if(($reassignments ?? collect())->isNotEmpty())
            <div class="ticket-reassignment-trail">
                @foreach($reassignments as $r)
                <div class="d-flex align-items-start gap-2 mb-3 {{ !$loop->last ? 'pb-3 border-bottom' : '' }}" style="border-color:var(--agile-border)!important">
                    <span class="ticket-reassign-arrow flex-shrink-0"><i class="bi bi-arrow-right-short text-muted"></i></span>
                    <div class="flex-grow-1 small">
                        <span class="text-muted">{{ e($r->from_user_name ?? 'Unassigned') }}</span>
                        <span class="text-muted mx-1">→</span>
                        <span class="fw-semibold">{{ e($r->to_user_name ?? '—') }}</span>
                        @if($r->reassigned_by_name ?? null)
                        <p class="text-muted mb-0 mt-1" style="font-size:0.75rem">by {{ e($r->reassigned_by_name) }} · {{ $r->created_at?->format('d M Y, H:i') ?? '' }}</p>
                        @else
                        <p class="text-muted mb-0 mt-1" style="font-size:0.75rem">{{ $r->created_at?->format('d M Y, H:i') ?? '' }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-muted small mb-0">No reassignments recorded. Changes are logged when a ticket is reassigned via the list or edit form.</p>
            @endif
        </div>
        <div class="app-card p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Details</h6>
            <dl class="ticket-details mb-0">
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--agile-border)!important">
                    <dt class="text-muted small mb-0">Status</dt>
                    <dd class="mb-0"><span class="ticket-status-badge ticket-status-{{ Str::slug($ticket->status ?? '') }}">{{ $ticket->status ?? '—' }}</span></dd>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--agile-border)!important">
                    <dt class="text-muted small mb-0">Assigned To</dt>
                    <dd class="mb-0">{{ $ticket->assigned_to_name ?? '—' }}</dd>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--agile-border)!important">
                    <dt class="text-muted small mb-0">Priority</dt>
                    <dd class="mb-0">{{ $ticket->priority ?? '—' }}</dd>
                </div>
                @if($ticket->category ?? null)
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--agile-border)!important">
                    <dt class="text-muted small mb-0">Category</dt>
                    <dd class="mb-0">{{ $ticket->category }}</dd>
                </div>
                @endif
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--agile-border)!important">
                    <dt class="text-muted small mb-0">Ticket #</dt>
                    <dd class="mb-0 font-monospace small">{{ $ticket->ticket_no ?? $ticket->ticketid }}</dd>
                </div>
                @if($ticket->createdtime ?? null)
                <div class="d-flex justify-content-between py-2">
                    <dt class="text-muted small mb-0">Created</dt>
                    <dd class="mb-0 small">{{ date('d M Y, H:i', strtotime($ticket->createdtime)) }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>

<style>
.ticket-workflow {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem 1rem;
    padding: 1rem 1.25rem; background: #f8fafc; border: 1px solid var(--agile-border); border-radius: 12px;
}
.ticket-workflow-step { display: flex; flex-direction: column; gap: 0.15rem; }
.ticket-workflow-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--agile-text-muted); }
.ticket-workflow-value { font-weight: 500; color: var(--agile-text); }
.ticket-workflow-arrow { color: var(--agile-border); font-size: 0.9rem; }
.ticket-status-badge {
    font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.75rem; border-radius: 9999px; display: inline-block;
}
.ticket-status-open { background: var(--agile-primary-muted); color: var(--agile-primary); }
.ticket-status-in-progress, .ticket-status-In-Progress { background: rgba(217, 119, 6, 0.15); color: #b45309; }
.ticket-status-wait-for-response, .ticket-status-Wait-For-Response { background: rgba(14, 165, 233, 0.15); color: #0284c7; }
.ticket-status-closed { background: rgba(5, 150, 105, 0.15); color: #059669; }
.ticket-status-inactive { background: rgba(107, 114, 128, 0.15); color: #6b7280; }
.ticket-description { line-height: 1.65; color: var(--agile-text); }
.ticket-comment-body { line-height: 1.6; color: var(--agile-text); font-size: 0.9rem; }
.btn-outline-danger { border-color: #fecaca; color: #dc2626; }
.btn-outline-danger:hover { background: #fef2f2; border-color: #dc2626; color: #dc2626; }
.kb-results { max-height: 26rem; overflow: auto; }
.kb-steps { line-height: 1.45; }
.kb-article { border: 1px solid var(--agile-border, #e2e8f0); border-radius: 10px; padding: 0.7rem 0.8rem; margin-bottom: 0.6rem; background: #fff; }
.kb-article-q { font-weight: 600; font-size: 0.85rem; color: var(--agile-text, #1e293b); }
.kb-article-cat { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; }
.kb-article-a { font-size: 0.82rem; color: #475569; line-height: 1.5; max-height: 4.5rem; overflow: hidden; transition: max-height 0.2s ease; }
.kb-article-a.expanded { max-height: 40rem; }
.kb-article-actions .btn { font-size: 0.72rem; }
</style>

@if(($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true))
<div class="modal fade" id="closeTicketModal" tabindex="-1" aria-labelledby="closeTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closeTicketModalLabel"><i class="bi bi-check-circle text-success me-2"></i>Close Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tickets.close', $ticket->ticketid) }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="closeTicketHint">Add a brief resolution (or leave blank to use "Closed").</p>
                    <label class="form-label fw-semibold">Solution <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="solution" id="ticketSolutionBox" class="form-control" rows="5" placeholder="e.g. Issue resolved using FAQ guidance…" autofocus>{{ old('solution') }}</textarea>
                    <div class="form-text" id="kbSolutionSource" style="display:none"><i class="bi bi-journal-bookmark me-1"></i>Filled from knowledge base</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Close Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
(function () {
    const panel = document.getElementById('kbPanel');
    if (!panel) return;
    const url = panel.getAttribute('data-search-url');
    const markUrl = panel.getAttribute('data-mark-url');
    const input = document.getElementById('kbSearchInput');
    const box = document.getElementById('kbResults');
    const canClose = panel.getAttribute('data-can-close') === '1';
    const seed = (panel.getAttribute('data-ticket-title') + ' ' + panel.getAttribute('data-ticket-category')).trim();
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function formatSolution(r) {
        return 'Resolved using FAQ guidance:\n'
            + r.question + '\n\n'
            + r.answer
            + (r.category ? ('\n\n(Source: ' + r.category + ' knowledge base)') : '');
    }

    function markHelpful(id) {
        if (!markUrl || !id) return;
        fetch(markUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ id: id }),
        }).catch(() => {});
    }

    function render(rows) {
        if (!rows.length) {
            box.innerHTML = '<p class="text-muted small mb-0">No matching FAQs. Try different keywords or open the FAQs page.</p>';
            return;
        }
        box.innerHTML = rows.map(r => {
            const solBtn = canClose
                ? `<button type="button" class="btn btn-sm btn-success" data-act="solution" data-id="${r.id}"><i class="bi bi-check-circle me-1"></i>Use as solution</button>`
                : '';
            return `<div class="kb-article" data-id="${r.id}">
                <div class="kb-article-cat mb-1">${escapeHtml(r.category)}</div>
                <div class="kb-article-q mb-1">${escapeHtml(r.question)}</div>
                <div class="kb-article-a mb-2" data-answer>${escapeHtml(r.answer).replace(/\n/g, '<br>')}</div>
                <div class="kb-article-actions d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-act="expand"><i class="bi bi-arrows-expand me-1"></i>More</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-act="comment" data-id="${r.id}"><i class="bi bi-chat-left-text me-1"></i>Insert into comment</button>
                    ${solBtn}
                </div>
            </div>`;
        }).join('');

        const store = {};
        rows.forEach(r => store[r.id] = r);

        box.querySelectorAll('[data-act]').forEach(btn => {
            btn.addEventListener('click', function () {
                const act = this.getAttribute('data-act');
                const card = this.closest('.kb-article');
                const id = this.getAttribute('data-id');
                const r = id ? store[id] : null;
                if (act === 'expand') {
                    card.querySelector('[data-answer]').classList.toggle('expanded');
                } else if (act === 'comment' && r) {
                    const t = document.getElementById('ticketCommentBox');
                    if (t) {
                        const ref = 'KB guidance — ' + r.question + '\n' + r.answer + '\n';
                        t.value = t.value ? (t.value.replace(/\s*$/, '') + '\n\n' + ref) : ref;
                        t.focus();
                        t.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        markHelpful(r.id);
                    }
                } else if (act === 'solution' && r) {
                    const s = document.getElementById('ticketSolutionBox');
                    const hint = document.getElementById('closeTicketHint');
                    const source = document.getElementById('kbSolutionSource');
                    if (s) { s.value = formatSolution(r); }
                    if (hint) { hint.textContent = 'Solution filled from the knowledge base. Review and close the ticket.'; }
                    if (source) { source.style.display = ''; }
                    markHelpful(r.id);
                    const modalEl = document.getElementById('closeTicketModal');
                    if (modalEl && window.bootstrap) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                }
            });
        });
    }

    let t;
    function doSearch(q) {
        fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => render((data && data.results) || []))
            .catch(() => { box.innerHTML = '<p class="text-muted small mb-0">Could not load knowledge base.</p>'; });
    }

    input.addEventListener('input', function () {
        clearTimeout(t);
        const q = this.value.trim();
        t = setTimeout(() => doSearch(q), 250);
    });

    // Initial suggestions based on the ticket subject + category.
    doSearch(seed);
})();
</script>
@endpush
@endsection
